<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Mailbox;
use App\Models\MailboxState;
use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Subscription;
use RuntimeException;

/**
 * "Cambiar de plan" en Mi cuenta. Los cambios de plan se hacen aquí, no en el portal de Stripe:
 * - Gratis → pago: Stripe Checkout (nueva suscripción del buzón); al pagar, el buzón cambia de plan.
 * - Pago → pago: cambio inmediato del Price de la suscripción. Subir cobra ya la diferencia prorrateada
 *   (y si el cobro falla, no se cambia nada); bajar deja la diferencia como saldo para los próximos pagos.
 * - Pago → gratis: la suscripción se cancela al final del periodo pagado; entonces el webhook lo pasa a
 *   gratis (MailboxBilling::ended). Se puede deshacer hasta ese día.
 */
class PlanChange
{
    public function __construct(private PaidSignup $paid, private MailboxBilling $billing) {}

    public function subscription(Mailbox $mailbox): ?Subscription
    {
        $subscription = $mailbox->user?->subscription("mailbox:{$mailbox->id}");

        return $subscription && ! $subscription->ended() && ! $subscription->incomplete() ? $subscription : null;
    }

    /**
     * Planes a los que puede pasar el buzón, con el motivo si no puede.
     *
     * @return Collection<int, array{plan: Plan, current: bool, reason: ?string}>
     */
    public function options(Mailbox $mailbox): Collection
    {
        $short = NamePriceTier::forLength(mb_strlen($mailbox->local_part)) !== null;
        $otherFree = $mailbox->user->mailboxes()->whereKeyNot($mailbox->id)->whereIn('status', ['active', 'suspended'])
            ->whereIn('plan_id', Plan::where('is_free', true)->pluck('id'))->exists();
        $stripeReady = app(StripeCatalog::class)->enabled();

        return Plan::visible()->ordered()->get()
            ->when(! $mailbox->plan?->isVisible(), fn ($plans) => $mailbox->plan ? $plans->prepend($mailbox->plan) : $plans)
            ->map(function (Plan $plan) use ($mailbox, $short, $otherFree, $stripeReady) {
                $reason = match (true) {
                    $plan->id === (int) $mailbox->plan_id => null,
                    $plan->is_free && $short => 'Los nombres cortos solo van con un plan de pago.',
                    $plan->is_free && $otherFree => 'Ya tienes un buzón gratis: cada cuenta incluye uno.',
                    $plan->is_free && $mailbox->usedBytes() > $plan->quota_bytes => 'Ocupas más de lo que permite: libera espacio primero.',
                    ! $plan->is_free && (! $stripeReady || ! $plan->stripe_price_id) => 'Muy pronto.',
                    default => null,
                };

                return ['plan' => $plan, 'current' => $plan->id === (int) $mailbox->plan_id, 'reason' => $reason];
            })->values();
    }

    /**
     * @return array{redirect?: string, status: string}
     *
     * @throws RuntimeException con un mensaje para el usuario
     */
    public function change(User $user, Mailbox $mailbox, Plan $target, bool $consent): array
    {
        $option = $this->options($mailbox)->first(fn ($o) => $o['plan']->id === $target->id);
        if (! $option || $option['current']) {
            throw new RuntimeException('Ese ya es tu plan.');
        }
        if ($option['reason']) {
            throw new RuntimeException($option['reason']);
        }
        if (MailboxState::find($mailbox->id)?->unpaid_since) {
            throw new RuntimeException('Antes de cambiar de plan, paga lo pendiente desde "Pago y facturas".');
        }

        $subscription = $this->subscription($mailbox);

        if ($target->is_free) {
            if (! $subscription) {
                $this->billing->applyPlan($mailbox, $target);

                return ['status' => "Listo: tu plan ahora es {$target->name}."];
            }
            $subscription->cancel();

            return ['status' => "Tu plan pasará a {$target->name} el {$subscription->ends_at->timezone('Europe/Madrid')->format('d/m/Y')}. Hasta entonces sigues con todo lo de tu plan actual."];
        }

        $prices = $this->paid->prices($target, $mailbox->local_part);

        if (! $subscription) {
            if (! $consent) {
                throw new RuntimeException('Marca la casilla para que el nuevo plan empiece en cuanto pagues.');
            }
            $checkout = Checkout::create(['user_id' => $user->id, 'mailbox_id' => $mailbox->id, 'plan_id' => $target->id,
                'kind' => 'change', 'immediate_start_consent_at' => now()]);
            try {
                return ['redirect' => $this->paid->openCheckout($checkout, $prices,
                    route('account.plan.return', $checkout->id).'?session_id={CHECKOUT_SESSION_ID}',
                    route('account.plan', $mailbox->id)), 'status' => ''];
            } catch (\Throwable $e) {
                report($e);
                $checkout->update(['status' => 'expired']);
                throw new RuntimeException('No hemos podido abrir el pago. Inténtalo de nuevo en unos minutos.');
            }
        }

        $current = $mailbox->plan;
        $upgrade = ! $current || $current->is_free || $this->monthly($target) > $this->monthly($current) || $target->interval !== $current->interval;
        try {
            $upgrade ? $subscription->pendingIfPaymentFails()->swapAndInvoice($prices) : $subscription->swap($prices);
        } catch (IncompletePayment) {
            throw new RuntimeException('No hemos podido cobrar la diferencia, así que no hemos cambiado nada. Revisa tu tarjeta en "Pago y facturas".');
        }
        $this->billing->applyPlan($mailbox, $target);

        return ['status' => $upgrade
            ? "Listo: tu plan ahora es {$target->name}. Hemos cobrado la diferencia hasta tu próxima renovación."
            : "Listo: tu plan ahora es {$target->name}. La diferencia se descontará de tus próximos pagos."];
    }

    /** Deshacer el paso a gratis mientras el plan de pago sigue activo. */
    public function resume(Mailbox $mailbox): void
    {
        $subscription = $mailbox->user?->subscription("mailbox:{$mailbox->id}");
        if (! $subscription?->onGracePeriod()) {
            throw new RuntimeException('No hay ningún cambio pendiente que deshacer.');
        }
        $subscription->resume();
    }

    private function monthly(Plan $plan): float
    {
        return $plan->interval === 'year' ? $plan->price_cents / 12 : $plan->price_cents;
    }
}
