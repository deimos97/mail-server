<?php

namespace App\Services;

use App\Mail\AccountDeleted;
use App\Models\AppPassword;
use App\Models\Mailbox;
use App\Models\MailboxExport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Borrado voluntario de la cuenta (D-009): inmediato y sin vuelta atrás.
 *
 * 1. Cada buzón queda marcado como borrado (`status=deleted`, `active=0`, `deleted_at`): deja de recibir,
 *    de entrar y de enviar al momento. Sus dispositivos y tokens se revocan.
 * 2. `mail-provision delete-content` borra su correo del disco. Si fallara, el buzón ya está inaccesible
 *    y `purge-deleted` nunca liberará el nombre mientras quede correo: se arregla a mano (está en el log).
 * 3. La fila del buzón se queda 90 días (el nombre sigue ocupado); luego la quita el timer `mail-purge`.
 * 4. El usuario de la web se borra (email, contraseña, atribución) y se le avisa en su email de recuperación.
 */
class AccountDeletion
{
    public function __construct(private MailProvision $provision, private OAuthServer $oauth) {}

    public function delete(User $user): void
    {
        $mailboxes = Mailbox::where('user_id', $user->id)->where('status', '!=', 'deleted')->get();

        foreach ($mailboxes as $mailbox) {
            $mailbox->update(['status' => 'deleted', 'active' => false, 'can_send' => false, 'deleted_at' => now()]);
            AppPassword::where('mailbox_id', $mailbox->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }
        $this->oauth->revokeForUser($user);

        foreach ($mailboxes as $mailbox) {
            if (! $this->provision->enabled()) {
                continue;
            }
            try {
                $this->provision->deleteContent($mailbox->email);
            } catch (RuntimeException $e) {
                Log::error("Borrar cuenta: no se pudo borrar el correo de {$mailbox->email}: {$e->getMessage()}");
            }
        }

        // Las copias descargables también son su correo
        foreach (MailboxExport::where('user_id', $user->id)->where('status', 'ready')->get() as $export) {
            app(MailboxExports::class)->discard($export);
        }

        Mail::to($user->email)->queue(new AccountDeleted($mailboxes->pluck('email')->all()));
        $user->delete();
    }
}
