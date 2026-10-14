<?php

namespace App\Mail\Support;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Mail\Mailables\Content;

/** Autoconfirmación al cliente al abrir un ticket. */
class TicketReceived extends SupportMail
{
    public function __construct(SupportTicket $ticket, public SupportMessage $message)
    {
        parent::__construct($ticket);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support.received');
    }
}
