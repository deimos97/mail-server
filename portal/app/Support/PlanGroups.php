<?php

namespace App\Support;

use App\Models\Plan;
use Illuminate\Support\Collection;

/**
 * Planes agrupados por tier: el mensual y el anual del mismo plan van en una tarjeta, con su propio selector
 * Anual / Mensual que arranca en el más barato. Los precios se enseñan siempre al mes (el anual, dividido
 * entre 12 y con el pago anual al lado). Vale para modelos `Plan` y para los arrays de la landing.
 */
final class PlanGroups
{
    /**
     * @param  Collection<int, mixed>  $plans  ya ordenados
     * @return Collection<int, array{key: string, name: string, plans: Collection<int, mixed>, default: mixed}>
     */
    public static function of(Collection $plans): Collection
    {
        return $plans
            // El gratis y los planes que ya no se ofrecen (el actual de alguien, p. ej.) van solos
            ->groupBy(fn ($p) => data_get($p, 'is_free') || ($p instanceof Plan && ! $p->isVisible())
                ? 'plan-'.data_get($p, 'slug') : data_get($p, 'tier'))
            ->map(function (Collection $group, string $key) {
                $monthly = $group->first(fn ($p) => data_get($p, 'interval') !== 'year');

                return [
                    'key' => $key,
                    'name' => data_get($monthly ?? $group->first(), 'name'),
                    'plans' => $group->sortByDesc(fn ($p) => data_get($p, 'interval') === 'year')->values(),
                    'default' => $group->sortBy(fn ($p) => self::yearCents($p))->first(),
                ];
            })->values();
    }

    /** Lo que cuesta un año a precio de lista: con esto se elige el más barato de cada grupo. */
    public static function yearCents(mixed $plan): int
    {
        $cents = (int) data_get($plan, 'price_cents');

        return data_get($plan, 'interval') === 'year' ? $cents : $cents * 12;
    }

    /** Un importe del plan, al mes (el de un plan anual, entre 12 y redondeado). */
    public static function perMonth(mixed $plan, int $cents): int
    {
        return data_get($plan, 'interval') === 'year' ? (int) round($cents / 12) : $cents;
    }
}
