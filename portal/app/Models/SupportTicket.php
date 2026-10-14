<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Ticket de soporte (D-018). Ver App\Services\Support. */
class SupportTicket extends Model
{
    use OnPortalDatabase;

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    /** "#0042": va en el asunto de todos los correos y así se enganchan las respuestas. */
    public function number(): string
    {
        return '#'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Asunto de los correos: "[#0042] No me llegan los correos". */
    public function emailSubject(): string
    {
        return "[{$this->number()}] {$this->subject}";
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }
}
