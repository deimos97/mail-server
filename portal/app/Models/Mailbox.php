<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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

    public function usage(): HasOne
    {
        return $this->hasOne(QuotaUsage::class, 'username', 'email');
    }

    public function lastLogins(): HasMany
    {
        return $this->hasMany(LastLogin::class, 'username', 'email');
    }

    /** Bytes usados. Sin fila todavía (Dovecot la crea con el primer cambio en el buzón) = vacío. */
    public function usedBytes(): int
    {
        return $this->usage?->bytes ?? 0;
    }

    /** Porcentaje de la cuota usado (0–100), o null si no tiene cuota. */
    public function usedPercent(): ?int
    {
        return $this->quota_bytes > 0 ? (int) min(100, round($this->usedBytes() * 100 / $this->quota_bytes)) : null;
    }

    /** Último acceso de un dispositivo (id de app_passwords) o del webmail (0). */
    public function lastLoginOf(int $device): ?Carbon
    {
        return $this->lastLogins->firstWhere('device', $device)?->at();
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
