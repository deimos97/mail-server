<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/** Prueba A/B. Ver App\Services\Experiments. */
class Experiment extends Model
{
    use OnPortalDatabase;

    protected $guarded = ['id'];

    protected $attributes = ['is_active' => false, 'variants' => '[]'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'variants' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function isRunning(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->is_active && count($this->variants ?? []) > 1
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->ends_at === null || $this->ends_at->gt($at));
    }

    /** @return array<string, string> overrides de la variante (clave de config/landing.php → texto) */
    public function overridesFor(string $variant): array
    {
        foreach ($this->variants ?? [] as $v) {
            if (($v['key'] ?? null) === $variant) {
                return array_filter((array) ($v['overrides'] ?? []), fn ($text) => filled($text));
            }
        }

        return [];
    }
}
