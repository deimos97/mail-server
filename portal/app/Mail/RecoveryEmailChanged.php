<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al email de recuperación anterior: lleva la dirección nueva medio oculta. */
class RecoveryEmailChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $masked;

    public function __construct(string $newEmail)
    {
        [$local, $domain] = explode('@', $newEmail, 2);
        $this->masked = mb_substr($local, 0, 2).str_repeat('•', max(1, mb_strlen($local) - 2)).'@'.$domain;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Has cambiado tu email de recuperación');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.recovery-email-changed', with: ['support' => config('mail.support_address')]);
    }
}
