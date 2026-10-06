<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Reglas de formato de los nombres de buzón. Hoy una sola fila global (domain_id NULL). */
class NameRule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'min_length' => 'integer',
            'max_length' => 'integer',
            'forbid_edge_symbols' => 'boolean',
            'forbid_consecutive_symbols' => 'boolean',
        ];
    }

    /** Las reglas que aplican a un dominio: las suyas si las tiene, si no las globales. */
    public static function for(?int $domainId = null): self
    {
        return static::query()
            ->when($domainId, fn ($q) => $q->where('domain_id', $domainId)->orWhereNull('domain_id'), fn ($q) => $q->whereNull('domain_id'))
            ->orderByRaw('domain_id IS NULL')
            ->first() ?? new static;
    }
}
