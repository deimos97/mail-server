<?php

namespace App\Services;

use App\Mail\MailboxExportReady;
use App\Models\Mailbox;
use App\Models\MailboxExport;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Copias del correo. La web no puede leer los buzones (son de vmail): deja una petición
 * `requests/<id>.req` y el script de root deja `files/<id>.zip` o `files/<id>.failed`.
 * `collect()` (cada minuto) recoge los resultados, avisa por email y caduca las copias viejas.
 */
class MailboxExports
{
    public function request(User $user, Mailbox $mailbox): MailboxExport
    {
        $current = $this->current($mailbox);
        if ($current && ($current->status === 'pending' || $current->isDownloadable())) {
            throw new RuntimeException('Ya tienes una copia de este buzón en marcha o lista para descargar.');
        }

        $export = MailboxExport::create(['user_id' => $user->id, 'mailbox_id' => $mailbox->id]);
        File::ensureDirectoryExists($this->dir('requests'));
        File::put($this->dir('requests')."/{$export->id}.req", $mailbox->email."\n");

        return $export;
    }

    /** La copia más reciente de un buzón. */
    public function current(Mailbox $mailbox): ?MailboxExport
    {
        return MailboxExport::where('mailbox_id', $mailbox->id)->latest('id')->first();
    }

    public function path(MailboxExport $export): string
    {
        return $this->dir('files')."/{$export->id}.zip";
    }

    public function collect(): void
    {
        foreach (MailboxExport::where('status', 'pending')->with('mailbox', 'user')->get() as $export) {
            $zip = $this->path($export);
            $failed = $this->dir('files')."/{$export->id}.failed";

            if (is_file($zip)) {
                $export->update(['status' => 'ready', 'size_bytes' => filesize($zip), 'ready_at' => now(),
                    'expires_at' => now()->addHours(config('mail_export.hours'))]);
                if ($export->user && $export->mailbox) {
                    Mail::to($export->user->email)->queue(new MailboxExportReady($export->mailbox->email, $export->expires_at));
                }
            } elseif (is_file($failed) || $export->created_at->lt(now()->subHours(config('mail_export.timeout_hours')))) {
                File::delete($failed);
                $export->update(['status' => 'failed']);
            }
        }

        foreach (MailboxExport::where('status', 'ready')->where('expires_at', '<', now())->get() as $export) {
            $this->discard($export);
        }
    }

    /** Borra el fichero y deja la copia como caducada. */
    public function discard(MailboxExport $export): void
    {
        File::delete($this->path($export));
        $export->update(['status' => 'expired']);
    }

    private function dir(string $sub): string
    {
        return rtrim(config('mail_export.dir'), '/').'/'.$sub;
    }
}
