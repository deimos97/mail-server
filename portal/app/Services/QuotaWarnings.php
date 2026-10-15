<?php

namespace App\Services;

use App\Mail\QuotaNotice;
use App\Models\Mailbox;
use App\Models\MailboxState;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos de espacio (D-029): un email al llegar al 80 % y otro al 100 % de la cuota, con el botón de subir de
 * plan. Cada nivel se avisa una vez; al bajar del 70 % (ha liberado espacio o ha subido de plan) se vuelve a
 * avisar si vuelve a llenarse. Los internos no. Lo lanza el scheduler cada hora.
 */
class QuotaWarnings
{
    public const LEVELS = [100, 80];

    public const RESET_BELOW = 70;

    public function run(): int
    {
        $sent = 0;
        Mailbox::with('usage', 'plan', 'user')->where('status', 'active')->whereNotNull('user_id')
            ->where('tier', '!=', 'system')->where('quota_bytes', '>', 0)
            ->each(function (Mailbox $mailbox) use (&$sent) {
                $percent = $mailbox->usedBytes() * 100 / $mailbox->quota_bytes;
                $state = MailboxState::for($mailbox->id);
                $level = collect(self::LEVELS)->first(fn ($l) => $percent >= $l);

                if ($level === null) {
                    if ($state->exists && $state->quota_warned_level && $percent < self::RESET_BELOW) {
                        $state->update(['quota_warned_level' => null]);
                    }

                    return;
                }
                if ((int) $state->quota_warned_level >= $level || ! $mailbox->user?->email) {
                    return;
                }

                $state->quota_warned_level = $level;
                $state->save();
                Mail::to($mailbox->user->email)->queue(new QuotaNotice($mailbox, $level));
                $sent++;
            });

        return $sent;
    }
}
