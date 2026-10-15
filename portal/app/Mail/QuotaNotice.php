<?php

namespace App\Mail;

use App\Models\Mailbox;
use App\Support\Bytes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso de espacio al 80 % o al 100 % (App\Services\QuotaWarnings). */
class QuotaNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $address;

    public string $quota;

    public bool $free;

    public int $mailboxId;

    /** El plan que proponemos al gratis (el destacado de pago), p. ej. "Básico" y "10 GB". */
    public ?string $upsellName = null;

    public ?string $upsellQuota = null;

    public function __construct(Mailbox $mailbox, public int $level)
    {
        $this->address = $mailbox->email;
        $this->quota = Bytes::format($mailbox->quota_bytes);
        $this->free = (bool) $mailbox->plan?->is_free;
        $this->mailboxId = $mailbox->id;
        if ($this->free && ($upsell = \App\Models\Plan::visible()->ordered()->where('is_free', false)->orderByDesc('is_highlighted')->first())) {
            $this->upsellName = $upsell->name;
            $this->upsellQuota = Bytes::format($upsell->quota_bytes);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->level >= 100 ? 'Tu buzón está lleno' : 'Tu buzón está casi lleno').' ('.$this->address.')');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quota', with: ['support' => config('mail.support_address')]);
    }
}
