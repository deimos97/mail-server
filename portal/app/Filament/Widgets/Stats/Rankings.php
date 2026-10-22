<?php

namespace App\Filament\Widgets\Stats;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/** Listas cortas: de dónde vienen las visitas, buzones por plan y quién más envía y recibe. */
class Rankings extends Widget
{
    use InteractsWithPageFilters, StatsWidget;

    protected string $view = 'filament.widgets.stats.rankings';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $u = $this->stats()->usage();

        return ['lists' => [
            'Origen de las visitas' => $this->stats()->visits()['sources'],
            'Buzones por plan' => $this->stats()->accounts()['by_plan'],
            'Más envían fuera' => $u['top_senders'],
            'Más reciben' => $u['top_receivers'],
        ]];
    }
}
