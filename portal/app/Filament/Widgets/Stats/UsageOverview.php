<?php

namespace App\Filament\Widgets\Stats;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsageOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters, StatsWidget;

    protected ?string $heading = 'Disco y uso del correo';

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $s = $this->stats()->storage();
        $u = $this->stats()->usage();
        $diskPct = $s['disk_total'] ? $s['disk_used'] / $s['disk_total'] * 100 : null;
        $perMailbox = fn (int $n) => $u['mailboxes'] ? self::number($n / $u['mailboxes'], 1).' por buzón' : '';

        return [
            Stat::make('Disco del servidor', $diskPct === null ? '—' : self::number($diskPct).' %')
                ->description(self::gigabytes($s['disk_used']).' de '.self::gigabytes($s['disk_total']))
                ->color($diskPct >= 80 ? 'danger' : ($diskPct >= 60 ? 'warning' : 'success')),
            Stat::make('Correo guardado', self::gigabytes($s['mailboxes_used']))
                ->description('De '.self::gigabytes($s['mailboxes_quota']).' de cuota repartida · '.self::gigabytes($s['vmail']).' en disco'),
            Stat::make('Uso medio de la cuota', $u['avg_quota_pct'] === null ? '—' : self::number($u['avg_quota_pct'], 2).' %')
                ->description(self::number($u['over_90']).' buzones por encima del 90 %')
                ->color($u['over_90'] ? 'warning' : 'gray'),
            Stat::make('Buzones en uso', self::number($u['active_30d']).' de '.self::number($u['mailboxes']))
                ->description('Han entrado en los últimos 30 días · '.self::number($u['never_used']).' sin estrenar'),
            Stat::make('Enviados fuera', self::number($u['sent']))
                ->description('Clientes: '.self::number($u['customer_sent']).' · '.$perMailbox($u['customer_sent']).' · '.self::number($u['mailboxes_sending']).' buzones envían')
                ->chart($u['sent_per_day'])->chartColor('primary'),
            Stat::make('Recibidos', self::number($u['received']))
                ->description('Clientes: '.self::number($u['customer_received']).' · '.$perMailbox($u['customer_received']))
                ->chart($u['received_per_day'])->chartColor('info'),
            Stat::make('Rechazados al entrar', self::number($u['rejected']))
                ->description('Spam, listas negras y direcciones que no existen'),
            Stat::make('Rebotes', self::number($u['bounced']))
                ->description('Envíos que el otro servidor no aceptó')
                ->color($u['bounced'] ? 'warning' : 'gray'),
        ];
    }
}
