<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisos del ciclo de vida de un buzón (D-009). Tipos: payment_failed, cancelled, ended, downgraded, aliases_trimmed, reactivated,
 * suspended, deletion_warning, deleted, inactive_warning, inactive_deleted.
 */
class LifecycleNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const SUBJECTS = [
        'payment_failed' => 'No hemos podido cobrar tu plan',
        'cancelled' => 'Has cancelado tu plan: perderás tu correo',
        'ended' => 'Tu plan ha terminado',
        'downgraded' => 'Tu correo ha pasado al plan gratis',
        'aliases_trimmed' => 'Has bajado de plan: algunos alias ya no funcionan',
        'reactivated' => 'Tu correo vuelve a funcionar',
        'suspended' => 'Hemos suspendido tu correo por falta de pago',
        'deletion_warning' => 'Vamos a borrar tu correo dentro de una semana',
        'deleted' => 'Hemos borrado tu correo',
        'inactive_warning' => 'Hace mucho que no usas tu correo',
        'inactive_deleted' => 'Hemos borrado tu correo por inactividad',
    ];

    public function __construct(public string $type, public string $address, public ?string $date = null, public ?string $deleteDate = null,
        public array $aliases = []) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->type].' ('.$this->address.')');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.lifecycle', with: ['support' => config('mail.support_address')]);
    }
}
