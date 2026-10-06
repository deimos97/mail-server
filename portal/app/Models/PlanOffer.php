<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Descuento programable sobre un plan. En Stripe se traduce a un Coupon (Fase 4). */
class PlanOffer extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'duration' => 'once',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isRunning(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->ends_at === null || $this->ends_at->gt($at));
    }

    /** Aplica el descuento a un precio en céntimos. Nunca baja de 0. */
    public function apply(int $priceCents): int
    {
        $discounted = $this->type === 'percent'
            ? (int) round($priceCents * (100 - min($this->value, 100)) / 100)
            : $priceCents - $this->value;

        return max(0, $discounted);
    }
}
