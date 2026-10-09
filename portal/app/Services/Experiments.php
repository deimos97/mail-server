<?php

namespace App\Services;

use App\Models\Experiment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pruebas A/B propias (admin → Experimentos). La variante se decide en el servidor, así la página llega
 * ya pintada (sin parpadeo) aunque el visitante no acepte cookies. Se mide en PostHog, solo con
 * consentimiento: la variante va en todos los eventos como `$feature/<clave>` (también en los del
 * servidor: alta y primer pago, por `users.attribution.experiments`).
 *
 * - Reparto: hash estable de un id aleatorio de la sesión + la clave, según el peso de cada variante.
 *   Una vez asignada, la variante se queda en la sesión aunque cambien los pesos.
 * - Textos: cada variante puede sustituir textos de config/landing.php ("hero.title" → "…").
 *   Para cambios que no son de texto, el código pregunta `variant('clave') === 'b'`.
 * - Para probar: `?variante=clave:b` fuerza una variante en esta sesión.
 */
class Experiments
{
    private ?Collection $running = null;

    /** Textos originales de la landing (las variantes se aplican siempre sobre ellos). */
    private static ?array $baseline = null;

    /** @return Collection<string, Experiment> */
    public function running(): Collection
    {
        return $this->running ??= Experiment::all()->filter->isRunning()->keyBy('key');
    }

    public function variant(string $key): string
    {
        $session = session();
        $assigned = (array) $session->get('experiments.assigned', []);
        $experiment = $this->running()->get($key);
        if (! $experiment) {
            return 'control';
        }
        if (isset($assigned[$key]) && $this->hasVariant($experiment, $assigned[$key])) {
            return $assigned[$key];
        }

        $assigned[$key] = $this->pick($experiment, $this->visitor());
        $session->put('experiments.assigned', $assigned);

        return $assigned[$key];
    }

    /** Asigna todos los experimentos activos y devuelve [clave => variante]. */
    public function assignAll(): array
    {
        foreach ($this->running()->keys() as $key) {
            $this->variant($key);
        }

        return $this->assigned();
    }

    /** Las variantes asignadas en esta sesión (solo de experimentos aún activos). */
    public function assigned(): array
    {
        return array_intersect_key((array) session('experiments.assigned', []), $this->running()->all());
    }

    /** `?variante=clave:variante` (QA): fuerza la variante en esta sesión. */
    public function forceFromQuery(?string $value): void
    {
        if (! $value || ! preg_match('/^([a-z0-9_-]+):([a-z0-9_-]+)$/', $value, $m)) {
            return;
        }
        $experiment = $this->running()->get($m[1]);
        if ($experiment && $this->hasVariant($experiment, $m[2])) {
            session()->put('experiments.assigned', [$m[1] => $m[2]] + (array) session('experiments.assigned', []));
        }
    }

    /** Aplica a config('landing.*') los textos de las variantes asignadas (para esta petición). */
    public function applyLandingOverrides(): void
    {
        self::$baseline ??= config('landing');
        config(['landing' => self::$baseline]);

        foreach ($this->assignAll() as $key => $variant) {
            foreach ($this->running()->get($key)->overridesFor($variant) as $path => $text) {
                if (config()->has("landing.$path") && is_string(config("landing.$path"))) {
                    config(["landing.$path" => $text]);
                }
            }
        }
    }

    private function pick(Experiment $experiment, string $visitor): string
    {
        $variants = collect($experiment->variants)->filter(fn ($v) => ($v['weight'] ?? 0) > 0)->values();
        $total = $variants->sum('weight');
        if ($total <= 0) {
            return 'control';
        }
        $point = crc32($visitor.'|'.$experiment->key) % $total;
        foreach ($variants as $v) {
            if ($point < $v['weight']) {
                return $v['key'];
            }
            $point -= $v['weight'];
        }

        return $variants->last()['key'];
    }

    private function hasVariant(Experiment $experiment, string $variant): bool
    {
        return collect($experiment->variants)->contains('key', $variant);
    }

    private function visitor(): string
    {
        if (! session()->has('experiments.visitor')) {
            session()->put('experiments.visitor', Str::random(20));
        }

        return session('experiments.visitor');
    }
}
