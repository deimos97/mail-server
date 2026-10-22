<?php

namespace App\Filament\Widgets\Stats;

class VisitsChart extends LineChart
{
    protected ?string $heading = 'Visitas por día';

    protected function series(): array
    {
        $v = $this->stats()->visits();

        return ['Visitantes' => [$v['visitors_per_day'], '#dc2626'], 'Páginas vistas' => [$v['pageviews_per_day'], '#f59e0b']];
    }
}
