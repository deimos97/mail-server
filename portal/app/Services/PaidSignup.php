<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Mailbox;
use App\Models\MailboxReservation;
use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use RuntimeException;

/**
 * Alta de pago (D-006: todo activo desde que paga).
 *
 * 1. `start()`: crea el buzón como `pending` (ocupa el nombre, pero ni recibe ni entra: Postfix y Dovecot
 *    piden activo) y una sesión de Stripe Checkout para la suscripción `mailbox:{id}` (plan + sobrecoste
 *    de nombre corto si toca + cupón si hay oferta; IVA incluido).
 * 2. `activate()`: al confirmarse el pago (vuelta del Checkout o webhook, lo que llegue antes) el buzón
 *    pasa a activo y puede enviar.
 * 3. `release()`: si el pago se cancela o la sesión caduca, el buzón pendiente desaparece y el nombre queda libre.
 */
class PaidSignup
{
    public const CHECKOUT_MINUTES = 30;   // mínimo que admite Stripe

    public function __construct(private NameAvailability $availability, private MailProvision $provision) {}

    /** @return string URL de Stripe Checkout */
    public function start(User $user, MailboxReservation $reservation, Plan $plan, string $token): string
    {
        if (! $plan->isVisible() || $plan->is_free || ! $plan->stripe_price_id) {
            throw new RuntimeException('Ese plan no está disponible ahora mismo.');
        }

        $domain = $reservation->domain;
        $check = $this->availability->check($reservation->local_part, $domain, withSuggestions: false, reservationOwner: $token);
        if (! $check->isAvailable()) {
            throw new RuntimeException('Ese nombre ya no está disponible. Elige otro.');
        }
        $prices = [$plan->stripe_price_id];
        if ($check->requiresPaidPlan()) {
            $tier = NamePriceTier::forLength(mb_strlen($check->localPart));
            $tierPrice = $plan->interval === 'year' ? $tier?->stripe_price_year_id : $tier?->stripe_price_id;
            if (! $tierPrice) {
                throw new RuntimeException('Los nombres cortos aún no se pueden contratar. Elige otro nombre.');
            }
            $prices[] = $tierPrice;
        }

        try {
            $mailbox = Mailbox::create([
                'domain_id' => $domain->id, 'user_id' => $user->id, 'plan_id' => $plan->id,
                'local_part' => $check->localPart, 'email' => $check->localPart.'@'.$domain->name,
                'password' => '{BLF-CRYPT}'.Hash::driver('bcrypt')->make(Str::random(48)),
                'quota_bytes' => $plan->quota_bytes, 'tier' => $plan->tier,
                'active' => false, 'status' => 'pending', 'can_send' => true,
            ]);
        } catch (QueryException) {
            throw new RuntimeException('Ese nombre ya no está disponible. Elige otro.');
        }
        $reservation->delete();

        $checkout = Checkout::create(['user_id' => $user->id, 'mailbox_id' => $mailbox->id, 'plan_id' => $plan->id,
            'immediate_start_consent_at' => now()]);

        try {
            $builder = $user->newSubscription("mailbox:{$mailbox->id}", $prices)
                ->withMetadata(['mailbox_id' => $mailbox->id, 'checkout_id' => $checkout->id]);
            if (($offer = $plan->currentOffer()) && $offer->stripe_coupon_id) {
                $builder->withCoupon($offer->stripe_coupon_id);
            }
            $session = $builder->checkout([
                'success_url' => route('signup.payment.return', $checkout->id).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('signup.payment.cancel', $checkout->id),
                'locale' => 'es',
                'expires_at' => now()->addMinutes(self::CHECKOUT_MINUTES)->timestamp,
                'metadata' => ['mailbox_id' => $mailbox->id, 'checkout_id' => $checkout->id],
            ])->asStripeCheckoutSession();
        } catch (\Throwable $e) {
            report($e);
            $this->release($checkout);
            throw new RuntimeException('No hemos podido abrir el pago. Inténtalo de nuevo en unos minutos.');
        }

        $checkout->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    /** Comprueba en Stripe si la sesión está pagada y, si lo está, activa el buzón. */
    public function confirm(Checkout $checkout): bool
    {
        if ($checkout->status === 'completed') {
            return true;
        }
        $session = Cashier::stripe()->checkout->sessions->retrieve($checkout->stripe_session_id);
        if ($session->status === 'complete' && in_array($session->payment_status, ['paid', 'no_payment_required'], true)) {
            $this->activate($checkout);

            return true;
        }

        return false;
    }

    /** Idempotente: la vuelta del Checkout y el webhook pueden llegar los dos. */
    public function activate(Checkout $checkout): void
    {
        $mailbox = $checkout->mailbox;
        if ($checkout->status === 'completed' || ! $mailbox) {
            return;
        }
        if ($mailbox->status === 'pending') {
            $mailbox->update(['status' => 'active', 'active' => true, 'can_send' => true]);
        }
        $checkout->update(['status' => 'completed']);
        app(ServerAnalytics::class)->subscriptionStarted($checkout);
    }

    /** Pago cancelado o caducado: el buzón pendiente desaparece y el nombre queda libre. */
    public function release(Checkout $checkout): void
    {
        if ($checkout->status !== 'open') {
            return;
        }
        $mailbox = $checkout->mailbox;
        if ($mailbox && $mailbox->status === 'pending') {
            if ($this->provision->enabled()) {
                try {
                    $this->provision->deletePending($mailbox->email);
                } catch (RuntimeException $e) {
                    Log::error("Alta de pago: no se pudo liberar {$mailbox->email}: {$e->getMessage()}");
                }
            } else {
                $mailbox->delete();   // solo en local y tests (en producción la web no tiene DELETE)
            }
        }
        $checkout->update(['status' => 'expired']);

        if ($checkout->stripe_session_id) {
            try {
                $session = Cashier::stripe()->checkout->sessions->retrieve($checkout->stripe_session_id);
                if ($session->status === 'open') {
                    Cashier::stripe()->checkout->sessions->expire($checkout->stripe_session_id);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
