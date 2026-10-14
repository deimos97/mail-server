<?php

namespace App\Console\Commands;

use App\Services\DailyStats;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** Datos de negocio en JSON para el resumen diario (server/…/mail-daily-report). Solo lectura. */
class DailyStatsCommand extends Command
{
    protected $signature = 'report:daily-stats {--since= : desde cuándo (ISO 8601; por defecto, hace 24 h)}';

    protected $description = 'Altas, ingresos, bajas, cuotas y soporte desde una fecha, en JSON';

    public function handle(DailyStats $stats): int
    {
        $since = $this->option('since') ? Carbon::parse($this->option('since')) : now()->subDay();
        $this->line(json_encode($stats->collect($since, now()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
