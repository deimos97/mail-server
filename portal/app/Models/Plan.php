<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de correo. Precios en céntimos, IVA incluido.
 *
 * Visible = is_active y dentro de [available_from, available_until). Que un plan deje de ser
 * visible no afecta a quien ya lo tiene contratado.
 */
class Plan extends Model
{
    protected $guarded = ['id'];

    /** Los mismos valores por defecto que la migración, para modelos aún sin recargar. */
    protected $attributes = [
        'is_free' => false,
        'price_cents' => 0,
        'currency' => 'EUR',
        'interval' => 'month',
        'max_aliases' => 0,
        'is_active' => true,
        'is_highlighted' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'price_cents' => 'integer',
            'quota_bytes' => 'integer',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(PlanOffer::class);
    }

    #[Scope]
    protected function visible(Builder $query, ?CarbonInterface $at = null): void
    {
        $at ??= now();

        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('available_from')->orWhere('available_from', '<=', $at))
            ->where(fn (Builder $q) => $q->whereNull('available_until')->orWhere('available_until', '>', $at));
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('price_cents');
    }

    public function isVisible(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->is_active
            && ($this->available_from === null || $this->available_from->lte($at))
            && ($this->available_until === null || $this->available_until->gt($at));
    }

    /** La oferta vigente; si se solapan varias, la que da más descuento. */
    public function currentOffer(?CarbonInterface $at = null): ?PlanOffer
    {
        if ($this->is_free) {
            return null;
        }

        return $this->offers
            ->filter(fn (PlanOffer $offer) => $offer->isRunning($at))
            ->sortByDesc(fn (PlanOffer $offer) => $this->price_cents - $offer->apply($this->price_cents))
            ->first();
    }

    /** Precio que se muestra y se cobra ahora (con la oferta aplicada, si la hay). */
    public function effectivePriceCents(?CarbonInterface $at = null): int
    {
        if ($this->is_free) {
            return 0;
        }

        return $this->currentOffer($at)?->apply($this->price_cents) ?? $this->price_cents;
    }
}
