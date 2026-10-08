<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  list<string>  $addresses */
    public function __construct(public array $addresses) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Hemos borrado tu cuenta');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.account-deleted', with: ['support' => config('mail.support_address')]);
    }
}
