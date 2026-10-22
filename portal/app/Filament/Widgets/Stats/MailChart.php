<?php

namespace App\Filament\Widgets\Stats;

class MailChart extends LineChart
{
    protected ?string $heading = 'Correo por día';

    protected int|string|array $columnSpan = 'full';

    protected function series(): array
    {
        $u = $this->stats()->usage();

        return ['Recibidos' => [$u['received_per_day'], '#2563eb'], 'Enviados fuera' => [$u['sent_per_day'], '#dc2626']];
    }
}
