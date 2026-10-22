<?php

namespace App\Filament\Widgets\Stats;

use App\Filament\Support\Fields;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters, StatsWidget;

    protected ?string $heading = 'Visitas, cuentas y dinero';

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $v = $this->stats()->visits();
        $a = $this->stats()->accounts();
        $arpu = $a['subscriptions'] ? intdiv($a['monthly_cents'], $a['subscriptions']) : null;

        return [
            Stat::make('Visitantes', self::number($v['visitors']))
                ->description(self::number($v['pageviews']).' páginas vistas (sin bots)')
                ->chart($v['visitors_per_day'])->chartColor('primary'),
            Stat::make('Cuentas nuevas', self::number($a['new_users']))
                ->description($a['conversion'] === null ? 'Sin visitas registradas' : 'Conversión '.self::number($a['conversion'], 2).' % de los visitantes'),
            Stat::make('Buzones gratis', self::number($a['free']))
                ->description('+'.self::number($a['new_free']).' en el periodo'),
            Stat::make('Buzones de pago', self::number($a['paid']))
                ->description('+'.self::number($a['new_paid']).' en el periodo')
                ->chart($a['new_paid_per_day'])->chartColor('success'),
            Stat::make('Ingresos al mes', Fields::formatEuros($a['monthly_cents']))
                ->description(self::number($a['subscriptions']).' suscripciones'.($arpu ? ' · '.Fields::formatEuros($arpu).' de media' : '').' (precio de lista)'),
            Stat::make('Pagos sin terminar', self::number($a['checkouts_abandoned']))
                ->description(self::number($a['checkouts_completed']).' pagos completados en el periodo')
                ->color($a['checkouts_abandoned'] ? 'warning' : 'gray'),
            Stat::make('Registrados sin buzón', self::number($a['users_without_mailbox']))
                ->description('Empezaron el alta y no la acabaron (desde siempre)'),
            Stat::make('Bajas', self::number($a['deleted']))
                ->description(self::number($a['unpaid_now']).' impagos ahora · '.self::number($a['suspended']).' suspendidos')
                ->color($a['unpaid_now'] ? 'danger' : 'gray'),
        ];
    }
}
