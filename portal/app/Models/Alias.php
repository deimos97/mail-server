<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alias (BD `mailserver`): Postfix entrega en `destination` lo que llega a `source` y deja enviar como
 * `source` al buzón dueño (`mailbox_id`). Los del sistema (postmaster@, abuse@) no tienen dueño. Un alias
 * borrado se queda inactivo en cuarentena hasta `release_at` (lo quita `mail-provision purge-deleted`).
 */
class Alias extends Model
{
    protected $connection = 'mailserver';

    protected $table = 'aliases';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'created_at' => 'datetime', 'deleted_at' => 'datetime', 'release_at' => 'datetime'];
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /** Los que el cliente ve y gestiona: con dueño y sin borrar. */
    protected function scopeLive(Builder $query): void
    {
        $query->whereNotNull('mailbox_id')->whereNull('deleted_at');
    }
}
