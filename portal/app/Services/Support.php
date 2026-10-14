<?php

namespace App\Services;

use App\Mail\Support\StaffNotice;
use App\Mail\Support\TicketReceived;
use App\Mail\Support\TicketReply;
use App\Models\Setting;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Soporte por tickets (Fase 7, D-018 a D-021). El cliente abre el ticket en /soporte y recibe una
 * autoconfirmación con "[#0042]" en el asunto; respondemos desde el admin y, si contesta por email,
 * `support:fetch` (App\Services\SupportInbox) engancha su respuesta al mismo ticket.
 */
class Support
{
    public const GUESTS_SETTING = 'support.guests';

    public function guestsAllowed(): bool
    {
        return (bool) Setting::get(self::GUESTS_SETTING, config('support.guests_default'));
    }

    public function setGuestsAllowed(bool $allowed): void
    {
        Setting::set(self::GUESTS_SETTING, $allowed);
    }

    /** @param  list<UploadedFile>  $images */
    public function open(?User $user, string $email, string $subject, string $body, array $images = [], ?string $ip = null): SupportTicket
    {
        [$ticket, $message] = DB::transaction(function () use ($user, $email, $subject, $body, $images, $ip) {
            $ticket = SupportTicket::create([
                'user_id' => $user?->id,
                'email' => Str::lower(trim($email)),
                'subject' => Str::limit(trim($subject), 150, ''),
                'ip' => $ip,
                'last_message_at' => now(),
            ]);
            $message = $ticket->messages()->create(['author' => 'customer', 'source' => 'web', 'body' => trim($body)]);
            foreach ($images as $image) {
                $this->attach($message, $image->getContent(), $image->getClientOriginalName(), (string) $image->getMimeType());
            }

            return [$ticket, $message];
        });

        $this->mailer()->to($ticket->email)->queue(new TicketReceived($ticket, $message));
        $this->notifyStaff($ticket, $message, isNew: true);

        return $ticket;
    }

    /** Nuestra respuesta desde el admin: se envía al cliente y, si se pide, cierra el ticket. */
    public function reply(SupportTicket $ticket, User $staff, string $body, bool $close = false): SupportMessage
    {
        $message = $ticket->messages()->create(['author' => 'staff', 'source' => 'admin', 'staff_id' => $staff->id, 'body' => trim($body)]);
        $ticket->update(['last_message_at' => now()] + ($close ? ['status' => 'closed', 'closed_at' => now()] : []));

        $this->mailer()->to($ticket->email)->queue(new TicketReply($ticket, $message));

        return $message;
    }

    /**
     * Respuesta del cliente por email (parte C). Reabre el ticket si estaba cerrado.
     *
     * @param  list<array{content: string, name: string, mime: string}>  $images
     */
    public function addCustomerEmail(SupportTicket $ticket, string $body, ?string $emailMessageId, array $images = []): SupportMessage
    {
        $message = DB::transaction(function () use ($ticket, $body, $emailMessageId, $images) {
            $message = $ticket->messages()->create([
                'author' => 'customer', 'source' => 'email', 'body' => trim($body) ?: '(Sin texto)', 'email_message_id' => $emailMessageId,
            ]);
            foreach ($images as $image) {
                $this->attach($message, $image['content'], $image['name'], $image['mime']);
            }
            $ticket->update(['status' => 'open', 'closed_at' => null, 'last_message_at' => now()]);

            return $message;
        });

        $this->notifyStaff($ticket, $message, isNew: false);

        return $message;
    }

    public function close(SupportTicket $ticket): void
    {
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
    }

    public function reopen(SupportTicket $ticket): void
    {
        $ticket->update(['status' => 'open', 'closed_at' => null]);
    }

    /**
     * Borra los tickets de un usuario con sus imágenes: los de su cuenta y los que escribió sin sesión
     * desde su email de recuperación. Se llama al borrar la cuenta (la política promete borrar sus datos).
     */
    public function deleteForUser(User $user): int
    {
        $tickets = SupportTicket::where('user_id', $user->id)
            ->orWhere(fn ($q) => $q->whereNull('user_id')->where('email', Str::lower($user->email)))
            ->get();

        foreach ($tickets as $ticket) {
            $this->delete($ticket);
        }

        return $tickets->count();
    }

    /** Borra un ticket, sus mensajes y sus imágenes del disco. */
    public function delete(SupportTicket $ticket): void
    {
        Storage::disk('local')->deleteDirectory('support/'.$ticket->id);
        $ticket->delete();   // mensajes y adjuntos van en cascada
    }

    private function attach(SupportMessage $message, string $content, string $name, string $mime): void
    {
        $extension = Str::lower(pathinfo($name, PATHINFO_EXTENSION)) ?: Str::after($mime, '/');
        $path = 'support/'.$message->support_ticket_id.'/'.Str::random(32).'.'.preg_replace('/[^a-z0-9]/', '', $extension);
        Storage::disk('local')->put($path, $content);

        $message->attachments()->create([
            'path' => $path,
            'name' => Str::limit(basename($name), 200, '') ?: 'imagen',
            'mime' => Str::limit($mime, 100, ''),
            'size' => strlen($content),
        ]);
    }

    /**
     * Mailer de soporte@ (D-019). Hay que elegirlo aquí: al encolar, Laravel pone en el Mailable el
     * mailer con el que se encola, y por el de siempre (noreply@) Postfix rechaza el remitente soporte@.
     */
    private function mailer(): \Illuminate\Contracts\Mail\Mailer
    {
        return Mail::mailer(config('support.mailer') ?: null);
    }

    private function notifyStaff(SupportTicket $ticket, SupportMessage $message, bool $isNew): void
    {
        if ($to = config('support.notify')) {
            Mail::to($to)->queue(new StaffNotice($ticket, $message, $isNew));
        }
    }
}
