<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Stats\MailChart;
use App\Filament\Widgets\Stats\Rankings;
use App\Filament\Widgets\Stats\SignupsChart;
use App\Filament\Widgets\Stats\TrafficOverview;
use App\Filament\Widgets\Stats\UsageOverview;
use App\Filament\Widgets\Stats\VisitsChart;
use App\Services\AdminStats;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Estadísticas del servicio: visitas, cuentas, dinero, disco y uso del correo, para los últimos N días. */
class Stats extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/estadisticas';

    protected static ?string $title = 'Estadísticas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = -1;

    public const PERIODS = [7 => 'Últimos 7 días', 30 => 'Últimos 30 días', 90 => 'Últimos 90 días'];

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('days')->label('Periodo')->options(self::PERIODS)->default(30)->selectablePlaceholder(false),
        ]);
    }

    public function getSubheading(): ?string
    {
        $at = AdminStats::for(30)->updatedAt();

        return 'Hora de Madrid. Visitas y correo se actualizan cada hora desde los logs del servidor'
            .($at ? ' (último dato: '.$at->format('d/m H:i').').' : '; aún no hay ninguno.');
    }

    public function getWidgets(): array
    {
        return [TrafficOverview::class, VisitsChart::class, SignupsChart::class, UsageOverview::class, MailChart::class, Rankings::class];
    }

    /** Lo que se ha elegido arriba, para los widgets. */
    public static function days(?array $filters): int
    {
        $days = (int) ($filters['days'] ?? 30);

        return array_key_exists($days, self::PERIODS) ? $days : 30;
    }
}
