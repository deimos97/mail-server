<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pago iniciado en el alta: el buzón queda `pending` hasta que Stripe confirma (webhook o vuelta del Checkout). */
class Checkout extends Model
{
    use OnPortalDatabase;

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return ['immediate_start_consent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }
}
