<?php

namespace App\Filament\Widgets\Stats;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SignupsChart extends ChartWidget
{
    use InteractsWithPageFilters, StatsWidget;

    protected ?string $heading = 'Buzones nuevos por día';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $a = $this->stats()->accounts();

        return [
            'labels' => $this->dayLabels(),
            'datasets' => [
                ['label' => 'De pago', 'data' => $a['new_paid_per_day'], 'backgroundColor' => '#16a34a'],
                ['label' => 'Gratis', 'data' => $a['new_free_per_day'], 'backgroundColor' => '#a8a29e'],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'ticks' => ['precision' => 0]]]];
    }
}
