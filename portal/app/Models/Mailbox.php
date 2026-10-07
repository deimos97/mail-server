<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Buzón (BD `mailserver`, la que leen Postfix y Dovecot).
 *
 * - `password` es una contraseña interna aleatoria que nadie conoce: el usuario entra con
 *   contraseñas por dispositivo (AppPassword) y, en el webmail, con el login único.
 * - status: pending · active · suspended (recibe pero no entra ni envía) · deleted.
 * - can_send = false hasta que el usuario verifica su email de recuperación (lo aplica Postfix).
 */
class Mailbox extends Model
{
    protected $connection = 'mailserver';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected $attributes = [
        'active' => true,
        'status' => 'active',
        'can_send' => false,
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'can_send' => 'boolean',
            'quota_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function appPasswords(): HasMany
    {
        return $this->hasMany(AppPassword::class);
    }

    /** El dueño vive en la BD `portal` (otra conexión): se consulta aparte, sin JOIN. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
