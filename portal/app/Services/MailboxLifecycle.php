<?php

namespace App\Services;

use App\Mail\LifecycleNotice;
use App\Models\AppPassword;
use App\Models\Mailbox;
use App\Models\MailboxExport;
use App\Models\MailboxState;
use App\Models\OauthToken;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Ciclo de vida diario (D-009, plazos en config/lifecycle.php):
 * - Impago: suspensión (día 10) → aviso (día 23) → borrado (día 30) → nombre libre (día 90).
 * - Gratis sin uso 6 meses: aviso → borrado a los 30 días → nombre libre 60 días después.
 * Aquí solo se marca el buzón como borrado: el correo lo borra root esa misma noche (mail-purge),
 * porque el scheduler no puede usar sudo. Nunca se libera un nombre con correo dentro.
 */
class MailboxLifecycle
{
    public function run(): void
    {
        $this->unpaid();
        $this->inactiveFree();
    }

    private function unpaid(): void
    {
        $c = config('lifecycle.unpaid');

        foreach (MailboxState::whereNotNull('unpaid_since')->get() as $state) {
            $mailbox = Mailbox::find($state->mailbox_id);
            if (! $mailbox || $mailbox->status === 'deleted') {
                continue;
            }
            $since = $state->unpaid_since;
            $deleteAt = $since->copy()->addDays($c['delete_after_days']);

            if (now()->gte($deleteAt)) {
                $this->delete($mailbox, $since->copy()->addDays($c['release_after_days']));
                $this->cancelSubscription($mailbox);
                $this->notify($mailbox, 'deleted');

                continue;
            }

            if ($mailbox->status === 'active' && ! $state->suspended_at && now()->gte($since->copy()->addDays($c['suspend_after_days']))) {
                $mailbox->update(['status' => 'suspended']);
                $state->update(['suspended_at' => now()]);
                $this->notify($mailbox, 'suspended', $deleteAt);
            }

            if (! $state->deletion_warned_at && now()->gte($deleteAt->copy()->subDays($c['warning_days_before_delete']))) {
                $state->update(['deletion_warned_at' => now()]);
                $this->notify($mailbox, 'deletion_warning', $deleteAt);
            }
        }
    }

    private function inactiveFree(): void
    {
        $c = config('lifecycle.inactive_free');
        $freePlans = Plan::where('is_free', true)->pluck('id');

        Mailbox::where('status', 'active')->whereNotNull('user_id')->where('tier', '!=', 'system')
            ->whereIn('plan_id', $freePlans)->with('lastLogins', 'user')
            ->each(function (Mailbox $mailbox) use ($c) {
                $last = $mailbox->lastLogins->max('last_login');
                $lastUse = $last ? Carbon::createFromTimestampUTC($last) : ($mailbox->created_at ?? now());
                $state = MailboxState::for($mailbox->id);

                if ($state->inactivity_warned_at && $lastUse->gt($state->inactivity_warned_at)) {
                    $state->fill(['inactivity_warned_at' => null])->save();   // ha vuelto: todo normal

                    return;
                }

                if (! $state->inactivity_warned_at && $lastUse->lt(now()->subMonths($c['after_months']))) {
                    $state->fill(['inactivity_warned_at' => now()])->save();
                    $this->notify($mailbox, 'inactive_warning', now()->addDays($c['grace_days']));

                    return;
                }

                if ($state->inactivity_warned_at && now()->gte($state->inactivity_warned_at->copy()->addDays($c['grace_days']))) {
                    $this->delete($mailbox, now()->addDays($c['release_after_days']));
                    $this->notify($mailbox, 'inactive_deleted');
                }
            });
    }

    /** Lo marca como borrado y corta todos los accesos. El correo lo borra root esa noche. */
    public function delete(Mailbox $mailbox, Carbon $releaseAt): void
    {
        $mailbox->update(['status' => 'deleted', 'active' => false, 'can_send' => false,
            'deleted_at' => now(), 'release_at' => $releaseAt]);
        AppPassword::where('mailbox_id', $mailbox->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        OauthToken::where('mailbox_id', $mailbox->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        foreach (MailboxExport::where('mailbox_id', $mailbox->id)->where('status', 'ready')->get() as $export) {
            app(MailboxExports::class)->discard($export);
        }
        Log::info("Ciclo de vida: {$mailbox->email} borrado; nombre libre el {$releaseAt->toDateString()}");
    }

    private function cancelSubscription(Mailbox $mailbox): void
    {
        $subscription = $mailbox->user?->subscription("mailbox:{$mailbox->id}");
        if ($subscription && ! $subscription->ended()) {
            try {
                $subscription->cancelNow();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function notify(Mailbox $mailbox, string $type, ?Carbon $date = null): void
    {
        if ($email = $mailbox->user?->email) {
            Mail::to($email)->queue(new LifecycleNotice($type, $mailbox->email, $date?->timezone('Europe/Madrid')->format('d/m/Y')));
        }
    }
}
