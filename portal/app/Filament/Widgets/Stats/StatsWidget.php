<?php

namespace App\Filament\Widgets\Stats;

use App\Filament\Pages\Stats;
use App\Services\AdminStats;

/** Lo común de los widgets de la página de estadísticas: solo salen allí y leen el periodo elegido. */
trait StatsWidget
{
    protected static bool $isDiscovered = false;

    /** Sin refresco automático: los datos de los logs cambian cada hora. */
    protected function getPollingInterval(): ?string
    {
        return null;
    }

    protected function stats(): AdminStats
    {
        return AdminStats::for(Stats::days($this->pageFilters));
    }

    protected static function number(int|float|null $n, int $decimals = 0): string
    {
        return $n === null ? '—' : number_format($n, $decimals, ',', '.');
    }

    protected static function gigabytes(?int $bytes): string
    {
        return $bytes === null ? '—' : self::number($bytes / 1024 ** 3, $bytes < 10 * 1024 ** 3 ? 2 : 1).' GB';
    }

    /** Etiquetas del eje X: 21/10 */
    protected function dayLabels(): array
    {
        return array_map(fn (string $day) => substr($day, 8, 2).'/'.substr($day, 5, 2), $this->stats()->dayKeys());
    }
}
