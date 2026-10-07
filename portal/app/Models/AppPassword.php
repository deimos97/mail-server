<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contraseña de un dispositivo (BD `mailserver`). Dovecot la busca por `selector` (los 6 primeros
 * caracteres de la contraseña) y compara el hash. La contraseña en claro solo se muestra una vez.
 */
class AppPassword extends Model
{
    protected $connection = 'mailserver';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }
}
