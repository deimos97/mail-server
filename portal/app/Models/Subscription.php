<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * Suscripción de Cashier. El IVA va en la suscripción (`default_tax_rates`, D-017), no en cada línea:
 * Cashier manda `items[][tax_rates]` vacío al cambiar de precio y Stripe lo rechaza en los cambios
 * "solo si se cobra" (`pending_if_incomplete`, al subir de plan). Aquí se quita cuando va vacío.
 */
class Subscription extends CashierSubscription
{
    protected function parseSwapPrices(array $prices): Collection
    {
        return parent::parseSwapPrices($prices)
            ->map(fn (array $payload) => array_filter($payload, fn ($value, $key) => $key !== 'tax_rates' || $value !== null, ARRAY_FILTER_USE_BOTH));
    }
}
