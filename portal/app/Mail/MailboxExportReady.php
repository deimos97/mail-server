<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MailboxExportReady extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $address, public Carbon $expiresAt) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu copia del correo está lista');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.mailbox-export-ready');
    }
}
