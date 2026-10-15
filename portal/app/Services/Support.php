<?php

namespace App\Services;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Jobs\SendTelegramMessage;
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

    /** Segundo interruptor (D-028): correos nuevos a soporte@ de quien no es cliente. Independiente del formulario. */
    public const EMAIL_GUESTS_SETTING = 'support.email_guests';

    public function emailGuestsAllowed(): bool
    {
        return (bool) Setting::get(self::EMAIL_GUESTS_SETTING, config('support.email_guests_default'));
    }

    public function setEmailGuestsAllowed(bool $allowed): void
    {
        Setting::set(self::EMAIL_GUESTS_SETTING, $allowed);
    }

    /** @param  list<UploadedFile>  $images */
    public function open(?User $user, string $email, string $subject, string $body, array $images = [], ?string $ip = null): SupportTicket
    {
        $images = array_map(fn (UploadedFile $f) => ['content' => $f->getContent(), 'name' => $f->getClientOriginalName(), 'mime' => (string) $f->getMimeType()], $images);

        return $this->create($user, $email, $subject, $body, $images, $ip, 'web', null);
    }

    /**
     * Correo nuevo a soporte@ (sin "[#NNNN]"): abre un ticket como el formulario (D-028).
     *
     * @param  list<array{content: string, name: string, mime: string}>  $images
     */
    public function openFromEmail(?User $user, string $email, string $subject, string $body, array $images, ?string $emailMessageId): SupportTicket
    {
        return $this->create($user, $email, $subject, $body, $images, null, 'email', $emailMessageId);
    }

    /** @param  list<array{content: string, name: string, mime: string}>  $images */
    private function create(?User $user, string $email, string $subject, string $body, array $images, ?string $ip, string $source, ?string $emailMessageId): SupportTicket
    {
        [$ticket, $message] = DB::transaction(function () use ($user, $email, $subject, $body, $images, $ip, $source, $emailMessageId) {
            $ticket = SupportTicket::create([
                'user_id' => $user?->id,
                'email' => Str::lower(trim($email)),
                'subject' => Str::limit(trim($subject), 150, '') ?: '(Sin asunto)',
                'ip' => $ip,
                'last_message_at' => now(),
            ]);
            $message = $ticket->messages()->create(['author' => 'customer', 'source' => $source, 'body' => trim($body) ?: '(Sin texto)',
                'email_message_id' => $emailMessageId]);
            foreach ($images as $image) {
                $this->attach($message, $image['content'], $image['name'], $image['mime']);
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

    /** Borra las consultas cuyo último mensaje pasa del plazo de conservación (tarea diaria). */
    public function deleteExpired(): int
    {
        $tickets = SupportTicket::where('last_message_at', '<', now()->subMonths(config('support.retention_months')))->get();

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
        if (app(Telegram::class)->enabled()) {
            SendTelegramMessage::dispatch($this->telegramText($ticket, $message, $isNew));
        }
    }

    /** Aviso de Telegram: número, asunto, quién, adjuntos y el mensaje (recortado). */
    public function telegramText(SupportTicket $ticket, SupportMessage $message, bool $isNew): string
    {
        $e = fn (?string $text) => Telegram::e($text);
        $attachments = $message->attachments()->count();
        $url = SupportTicketResource::getUrl('view', ['record' => $ticket], panel: 'admin');

        return implode("\n", [
            ($isNew ? '🎫 <b>Ticket nuevo '.$ticket->number().'</b>' : '💬 <b>Respuesta en '.$ticket->number().'</b>').($message->source === 'email' ? ' (por email)' : ''),
            '<b>Asunto:</b> '.$e($ticket->subject),
            '<b>De:</b> '.$e($ticket->email).($ticket->user_id ? '' : ' · sin sesión'),
            '<b>Adjuntos:</b> '.($attachments ? trans_choice('{1} 1 imagen|[2,*] :count imágenes', $attachments) : 'ninguno'),
            '',
            $e(Str::limit($message->body, 1200, ' […]')),
            '',
            '<a href="'.$e($url).'">Abrir en el admin</a>',
        ]);
    }
}
