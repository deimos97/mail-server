<?php

namespace App\Mail\Support;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Mail\Mailables\Content;

/** Nuestra respuesta desde el admin. */
class TicketReply extends SupportMail
{
    public function __construct(SupportTicket $ticket, public SupportMessage $message)
    {
        parent::__construct($ticket);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support.reply');
    }
}
