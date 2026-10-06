<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sobrecoste mensual por longitud del nombre. Solo se puede coger con planes de pago (D-005). */
class NamePriceTier extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'min_length' => 'integer',
            'max_length' => 'integer',
            'price_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function forLength(int $length): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('min_length', '<=', $length)
            ->where('max_length', '>=', $length)
            ->orderByDesc('price_cents')
            ->first();
    }
}
