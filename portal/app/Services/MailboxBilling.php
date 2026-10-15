<?php

namespace App\Services;

use App\Mail\LifecycleNotice;
use App\Models\Mailbox;
use App\Models\MailboxState;
use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Support\Facades\Mail;

/**
 * Mantiene cada buzón de pago en sintonía con su suscripción de Stripe (la manda Stripe por webhook):
 * - Plan: por el Price de la suscripción (actual o antiguo, ver PlanPrice) → plan, cuota y `tier` al momento.
 * - Pagos: impago → `unpaid_since` (el ciclo de vida suspende y borra, ver MailboxLifecycle); pago → todo normal.
 * - Fin de la suscripción (cancelada al acabar el periodo, o sin pagar): pasa a gratis si se puede (D-009);
 *   si no, sigue el camino del impago.
 */
class MailboxBilling
{
    /** @param  array  $subscription  objeto `subscription` de Stripe (del webhook) */
    public function sync(array $subscription): void
    {
        $mailbox = $this->mailbox($subscription);
        if (! $mailbox || $mailbox->status === 'deleted' || $mailbox->isInternal()) {
            return;   // los internos no tienen plan: una suscripción vieja (p. ej. del sandbox) no les afecta
        }

        $plan = collect($subscription['items']['data'] ?? [])
            ->map(fn ($item) => PlanPrice::planFor($item['price']['id'] ?? null))
            ->filter()->first();
        if ($plan && (int) $mailbox->plan_id !== $plan->id && $gone = $this->applyPlan($mailbox, $plan)) {
            $this->notify($mailbox, 'aliases_trimmed', aliases: $gone);   // bajada hecha fuera de la web (p. ej. desde Stripe)
        }

        match ($subscription['status'] ?? null) {
            'active', 'trialing' => $this->paid($mailbox),
            'past_due', 'unpaid' => $this->unpaid($mailbox),
            default => null,
        };
    }

    /** La suscripción ha terminado (cancelada al final del periodo, o impagada hasta el final). */
    /** Si al acabar su suscripción el buzón puede pasar a gratis (D-009); si no, se trata como un impago. */
    public function fitsFree(Mailbox $mailbox): bool
    {
        $free = Plan::where('is_free', true)->orderBy('sort_order')->first();
        $user = $mailbox->user;

        return $free
            && $user && ! $user->mailboxes()->whereKeyNot($mailbox->id)->where('status', '!=', 'deleted')->whereIn('plan_id', Plan::where('is_free', true)->pluck('id'))->exists()
            && $mailbox->usedBytes() <= $free->quota_bytes
            && ! NamePriceTier::forLength(mb_strlen($mailbox->local_part));      // los nombres cortos solo van con pago
    }

    public function ended(array $subscription): void
    {
        $mailbox = $this->mailbox($subscription);
        if (! $mailbox || $mailbox->status === 'deleted' || $mailbox->isInternal()) {
            return;   // los internos no tienen plan: una suscripción vieja (p. ej. del sandbox) no les afecta
        }

        $free = Plan::where('is_free', true)->orderBy('sort_order')->first();
        $user = $mailbox->user;

        if ($this->fitsFree($mailbox)) {
            $gone = $this->applyPlan($mailbox, $free);
            $mailbox->update(['can_send' => $user->hasVerifiedEmail()]);
            $this->paid($mailbox);
            $this->notify($mailbox, 'downgraded', aliases: $gone);

            return;
        }

        $this->unpaid($mailbox, notify: false);
        $this->notify($mailbox, 'ended');
    }

    /** Un pago ha fallado: avisamos para que actualice la tarjeta (Stripe reintenta solo). */
    public function paymentFailed(?Mailbox $mailbox): void
    {
        if ($mailbox && $mailbox->status !== 'deleted' && ! $mailbox->isInternal()) {
            $this->unpaid($mailbox);
        }
    }

    /**
     * Cambia el plan del buzón; los alias que ya no caben se quitan (los más nuevos, a cuarentena).
     *
     * @return list<string> los alias quitados
     */
    public function applyPlan(Mailbox $mailbox, Plan $plan): array
    {
        $mailbox->update(['plan_id' => $plan->id, 'quota_bytes' => $plan->quota_bytes, 'tier' => $plan->tier]);

        return app(Aliases::class)->trimToPlan($mailbox->setRelation('plan', $plan));
    }

    public function mailboxFromMetadata(array $object): ?Mailbox
    {
        $id = $object['metadata']['mailbox_id'] ?? null;
        if (! $id && preg_match('/^mailbox:(\d+)$/', (string) ($object['metadata']['type'] ?? ''), $m)) {
            $id = $m[1];
        }

        return $id ? Mailbox::find((int) $id) : null;
    }

    private function mailbox(array $subscription): ?Mailbox
    {
        return $this->mailboxFromMetadata($subscription);
    }

    private function paid(Mailbox $mailbox): void
    {
        $state = MailboxState::for($mailbox->id);
        $wasSuspended = $state->suspended_at !== null;
        $state->fill(['unpaid_since' => null, 'suspended_at' => null, 'deletion_warned_at' => null])->save();

        if ($wasSuspended && $mailbox->status === 'suspended') {
            $mailbox->update(['status' => 'active']);
            $this->notify($mailbox, 'reactivated');
        }
    }

    private function unpaid(Mailbox $mailbox, bool $notify = true): void
    {
        $state = MailboxState::for($mailbox->id);
        if ($state->unpaid_since === null) {
            $state->unpaid_since = now();
            $state->save();
            if ($notify) {
                $this->notify($mailbox, 'payment_failed');
            }
        }
    }

    /** @param  list<string>  $aliases  alias quitados al bajar de plan */
    public function notify(Mailbox $mailbox, string $type, ?string $date = null, ?string $deleteDate = null, array $aliases = []): void
    {
        if ($email = $mailbox->user?->email) {
            Mail::to($email)->queue(new LifecycleNotice($type, $mailbox->email, $date, $deleteDate, $aliases));
        }
    }
}
