<?php

namespace App\Mail\Support;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso interno (a SUPPORT_NOTIFY) de ticket nuevo o de respuesta del cliente. Sale del remitente general. */
class StaffNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket, public SupportMessage $message, public bool $isNew) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->isNew ? 'Ticket nuevo ' : 'Respuesta en ').$this->ticket->emailSubject());
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support.staff-notice', with: [
            'url' => SupportTicketResource::getUrl('view', ['record' => $this->ticket], panel: 'admin'),
        ]);
    }
}
