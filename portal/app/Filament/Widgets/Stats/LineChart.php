<?php

namespace App\Filament\Widgets\Stats;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Gráfica por día del periodo: cada subclase dice qué series pinta. */
abstract class LineChart extends ChartWidget
{
    use InteractsWithPageFilters, StatsWidget;

    protected ?string $maxHeight = '260px';

    /** @return array<string, array{0: list<int>, 1: string}> etiqueta → [valores, color] */
    abstract protected function series(): array;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        return [
            'labels' => $this->dayLabels(),
            'datasets' => collect($this->series())->map(fn ($s, $label) => [
                'label' => $label, 'data' => $s[0], 'borderColor' => $s[1], 'backgroundColor' => $s[1].'33', 'fill' => true, 'tension' => 0.3, 'pointRadius' => 2,
            ])->values()->all(),
        ];
    }
}
