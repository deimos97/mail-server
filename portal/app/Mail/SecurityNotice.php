<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso de cambios de seguridad en la cuenta: two_factor_on, two_factor_off, passkey_added. */
class SecurityNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const SUBJECTS = [
        'two_factor_on' => 'Has activado la verificación en dos pasos',
        'two_factor_off' => 'Has desactivado la verificación en dos pasos',
        'passkey_added' => 'Has añadido una passkey a tu cuenta',
    ];

    public function __construct(public string $type, public ?string $detail = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->type]);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.security', with: ['support' => config('mail.support_address')]);
    }
}
