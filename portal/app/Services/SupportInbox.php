<?php

namespace App\Services;

use App\Mail\Support\UnmatchedNotice;
use App\Models\Mailbox as MailAccount;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Support\EmailReplyText;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MessageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Parte C del soporte (D-018): lee el buzón soporte@ cada minuto (`support:fetch`) y añade al
 * ticket las respuestas del cliente que llevan "[#0042]" en el asunto. Los correos nuevos (sin número)
 * abren un ticket como el formulario (D-028), salvo respuestas automáticas, rebotes, listas, spam,
 * remitentes del sistema o más de NEW_PER_HOUR por remitente (evita bucles con autorespondedores).
 * Los procesados se mueven a la carpeta "Tickets"; los demás se quedan en la bandeja de entrada sin
 * leer, para mirarlos a mano, marcados con la etiqueta UglChecked para no volver a procesarlos.
 */
class SupportInbox
{
    public const CHECKED = 'UglChecked';

    /** Tickets nuevos por email de un mismo remitente en una hora; a partir de ahí, a mano. */
    public const NEW_PER_HOUR = 3;

    /** Remitentes que nunca abren ticket (rebotes y sistemas). */
    private const SYSTEM_SENDERS = ['mailer-daemon', 'postmaster', 'noreply', 'no-reply', 'donotreply', 'do-not-reply', 'bounce', 'bounces'];

    public function __construct(private Support $support) {}

    public function configured(): bool
    {
        return filled(config('support.imap.username')) && filled(config('support.imap.password'));
    }

    /** @return int respuestas añadidas a algún ticket */
    public function fetch(): int
    {
        if (! $this->configured()) {
            return 0;
        }

        $config = config('support.imap');
        $mailbox = new Mailbox([
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'validate_cert' => $config['validate_cert'],
            'username' => $config['username'],
            'password' => $config['password'],
            'timeout' => 20,
        ]);

        $added = 0;
        try {
            $inbox = $mailbox->inbox();
            $messages = $inbox->messages()->unkeyword(self::CHECKED)->leaveUnread()->withHeaders()->withBody()->withFlags()->limit(50)->get();
            $folder = null;

            foreach ($messages as $message) {
                $saved = $email = null;
                try {
                    $email = $this->toArray($message);
                    $saved = $this->handle($email);
                } catch (Throwable $e) {
                    Log::warning('support:fetch no ha podido procesar un correo', ['uid' => $message->uid(), 'error' => $e->getMessage()]);
                }

                if ($saved) {
                    $added++;
                    $folder ??= $mailbox->folders()->firstOrCreate($config['processed_folder']);
                    $message->markSeen();
                    $message->move($folder->path(), expunge: true);
                } else {
                    $message->flag(self::CHECKED, '+');
                    $this->forwardUnmatched($email);
                }
            }
        } finally {
            $mailbox->disconnect();
        }

        return $added;
    }

    /**
     * Decide qué hacer con un correo ya leído. Devuelve el mensaje añadido a un ticket (respuesta o ticket
     * nuevo), o null si se queda en el buzón para mirarlo a mano.
     *
     * @param  array{message_id: ?string, from: ?string, subject: ?string, text: ?string, html: ?string, auto_submitted: ?string, images: list<array{content: string, name: string, mime: string}>}  $email
     */
    public function handle(array $email): ?SupportMessage
    {
        // Respuestas automáticas (fuera de la oficina, rebotes): ni reabren ni abren tickets
        if (filled($email['auto_submitted']) && Str::lower((string) $email['auto_submitted']) !== 'no') {
            return null;
        }
        if (! preg_match('/\[#0*(\d{1,9})\]/', (string) $email['subject'], $match)) {
            return $this->newTicket($email);
        }

        $ticket = SupportTicket::find((int) $match[1]);
        $from = Str::lower(trim((string) $email['from']));
        if (! $ticket || ! in_array($from, $this->customerAddresses($ticket), true)) {
            return null;   // número que no existe o remitente ajeno: que lo mire una persona
        }
        if ($email['message_id'] && SupportMessage::where('email_message_id', $email['message_id'])->exists()) {
            return null;
        }

        $text = filled($email['text']) ? (string) $email['text'] : EmailReplyText::fromHtml((string) $email['html']);

        return $this->support->addCustomerEmail($ticket, EmailReplyText::clean($text), $email['message_id'], $this->allowedImages($email['images']));
    }

    /** Correo nuevo (sin número de ticket): abre uno, si pasa los filtros. Devuelve su primer mensaje. */
    private function newTicket(array $email): ?SupportMessage
    {
        $from = Str::lower(trim((string) $email['from']));
        if (! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        [$local, $domain] = explode('@', $from, 2);
        $ownDomains = \App\Models\Domain::query()->pluck('name')->map(fn ($d) => Str::lower($d))->all();
        $ownSystem = in_array($domain, $ownDomains, true)
            && MailAccount::where('email', $from)->where('tier', 'system')->whereNull('user_id')->exists();   // soporte@, noreply@
        if (in_array($local, self::SYSTEM_SENDERS, true) || $ownSystem) {
            return null;
        }
        $precedence = Str::lower((string) ($email['precedence'] ?? ''));
        if (in_array($precedence, ['bulk', 'list', 'junk'], true) || filled($email['list_id'] ?? null)
            || Str::startsWith(Str::lower((string) ($email['spam'] ?? '')), 'yes')) {
            return null;
        }
        if ($email['message_id'] && SupportMessage::where('email_message_id', $email['message_id'])->exists()) {
            return null;
        }
        if (SupportTicket::where('email', $from)->where('created_at', '>=', now()->subHour())->count() >= self::NEW_PER_HOUR) {
            return null;
        }

        // Si es de un cliente (por su email de recuperación o uno de sus buzones), el ticket va a su cuenta
        $user = \App\Models\User::where('email', $from)->first()
            ?? MailAccount::where('email', $from)->whereNotNull('user_id')->first()?->user;
        if (! $user && ! $this->support->guestsAllowed()) {
            return null;   // tickets sin cuenta desactivados en el admin (D-021)
        }

        $text = filled($email['text']) ? (string) $email['text'] : EmailReplyText::fromHtml((string) $email['html']);
        $ticket = $this->support->openFromEmail($user, $from, (string) $email['subject'], EmailReplyText::clean($text),
            $this->allowedImages($email['images']), $email['message_id']);

        return $ticket->messages()->first();
    }

    /** Aviso a SUPPORT_NOTIFY de un correo que no es de ningún ticket (una sola vez: luego lleva la etiqueta). */
    public function forwardUnmatched(?array $email): void
    {
        if (! ($to = config('support.notify'))) {
            return;
        }
        $body = $email ? (filled($email['text']) ? (string) $email['text'] : EmailReplyText::fromHtml((string) $email['html'])) : '';
        Mail::to($to)->queue(new UnmatchedNotice($email['from'] ?? null, (string) ($email['subject'] ?? ''), trim($body) ?: '(No se ha podido leer el contenido; míralo en el buzón soporte@.)'));
    }

    /** El email del ticket y, si tiene cuenta, el de recuperación y sus buzones. */
    private function customerAddresses(SupportTicket $ticket): array
    {
        $addresses = [Str::lower($ticket->email)];
        if ($ticket->user) {
            $addresses[] = Str::lower($ticket->user->email);
            $addresses = [...$addresses, ...MailAccount::where('user_id', $ticket->user_id)->pluck('email')->map(fn ($e) => Str::lower($e))];
        }

        return array_values(array_unique($addresses));
    }

    /** Mismas reglas que el formulario (D-020): solo imágenes, hasta 3 y 8 MB en total. */
    private function allowedImages(array $images): array
    {
        $limits = config('support.attachments');
        $allowed = [];
        $total = 0;
        foreach ($images as $image) {
            $extension = Str::lower(pathinfo($image['name'], PATHINFO_EXTENSION));
            if (! str_starts_with($image['mime'], 'image/') || ! in_array($extension, $limits['mimes'], true)) {
                continue;
            }
            $total += strlen($image['content']);
            if (count($allowed) >= $limits['max_files'] || $total > $limits['max_total_kb'] * 1024) {
                break;
            }
            $allowed[] = $image;
        }

        return $allowed;
    }

    private function toArray(MessageInterface $message): array
    {
        $images = [];
        foreach ($message->attachments() as $attachment) {
            // También las pegadas dentro del texto (inline): suelen ser las capturas
            if (str_starts_with(Str::lower($attachment->contentType()), 'image/')) {
                $images[] = ['content' => $attachment->contents(), 'name' => $attachment->filename() ?: 'imagen.'.$attachment->extension(), 'mime' => Str::lower($attachment->contentType())];
            }
        }

        return [
            'message_id' => $message->messageId(),
            'from' => $message->from()?->email(),
            'subject' => $message->subject(),
            'text' => $message->text(),
            'html' => $message->html(),
            'auto_submitted' => $message->header('Auto-Submitted')?->getValue(),
            'precedence' => $message->header('Precedence')?->getValue(),
            'list_id' => $message->header('List-Id')?->getValue(),
            'spam' => $message->header('X-Spam')?->getValue(),
            'images' => $images,
        ];
    }
}
