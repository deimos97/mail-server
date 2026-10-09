<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Página pública de estado del servicio (/estado), a partir de lo que comprueba mail-monitor. */
class StatusController extends Controller
{
    public function __invoke(): View
    {
        $file = config('status.file');
        $data = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
        $checkedAt = isset($data['checked_at']) ? Carbon::createFromTimestamp((int) $data['checked_at']) : null;
        $stale = ! $checkedAt || $checkedAt->lt(now()->subMinutes(config('status.stale_minutes')));
        $problems = (array) ($data['problems'] ?? []);

        $components = collect(config('status.components'))->map(fn ($c) => [
            'label' => $c['label'],
            'ok' => ! collect($problems)->contains(fn ($p) => collect($c['checks'])->contains(fn ($pattern) => fnmatch($pattern, $p))),
        ]);

        return view('status', [
            'components' => $components,
            'checkedAt' => $checkedAt,
            'stale' => $stale,
            'allOk' => ! $stale && $components->every('ok'),
        ]);
    }
}
