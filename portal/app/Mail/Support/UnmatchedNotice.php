<?php

namespace App\Mail\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Correo en soporte@ que no es respuesta de un ticket (sin [#NNNN], remitente ajeno, rebotes…).
 * Se reenvía a SUPPORT_NOTIFY una vez, con "Responder a" al remitente, para no tener que entrar en soporte@.
 */
class UnmatchedNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ?string $sender, public string $originalSubject, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'En soporte@: '.Str::limit($this->originalSubject ?: '(sin asunto)', 150),
            replyTo: $this->sender && filter_var($this->sender, FILTER_VALIDATE_EMAIL) ? [new Address($this->sender)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support.unmatched');
    }
}
