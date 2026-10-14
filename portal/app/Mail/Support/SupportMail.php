<?php

namespace App\Mail\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Correos al cliente de un ticket: salen de soporte@ (D-019) con "[#0042]" en el asunto, que es
 * lo que usa `support:fetch` para enganchar su respuesta al ticket. Responder va a soporte@.
 */
abstract class SupportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket)
    {
        if ($mailer = config('support.mailer')) {
            $this->mailer($mailer);
        }
    }

    public function envelope(): Envelope
    {
        $from = new Address(config('support.from.address'), config('support.from.name'));

        return new Envelope(from: $from, replyTo: [$from], subject: $this->ticket->emailSubject());
    }

    /** Mismo hilo en Gmail/Apple Mail aunque el cliente cambie el asunto. */
    public function headers(): Headers
    {
        return new Headers(references: [$this->threadId()]);
    }

    protected function threadId(): string
    {
        return 'ticket-'.$this->ticket->id.'@'.(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
    }
}
