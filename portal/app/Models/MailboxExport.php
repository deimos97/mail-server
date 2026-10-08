<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Copia descargable de un buzón (un .mbox por carpeta, en un zip). Ver App\Services\MailboxExports. */
class MailboxExport extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'ready_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'ready' && $this->expires_at?->isFuture();
    }
}
