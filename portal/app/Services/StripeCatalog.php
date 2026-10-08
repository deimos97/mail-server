<?php

namespace App\Services;

use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\PlanOffer;
use App\Models\PlanPrice;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

/**
 * Catálogo en Stripe a partir de la BD (la BD manda; la landing nunca lee de Stripe):
 * - Plan de pago → Product + Price. Un Price no se puede cambiar: si cambia el precio o el intervalo se
 *   crea otro, el plan apunta al nuevo, el viejo se archiva y sus suscriptores lo conservan (D-007).
 * - Tramo de nombre corto → Product + Price mensual y anual (la suscripción entera va al mismo intervalo).
 * - Oferta → Coupon limitado al producto del plan. Un Coupon tampoco se puede cambiar: si cambian sus
 *   condiciones se crea otro.
 * - IVA: un único Tax Rate del 21 % incluido en el precio (D-017).
 */
class StripeCatalog
{
    public const TAX_RATE_KEY = 'iva-21-incluido';

    public function enabled(): bool
    {
        return filled(config('cashier.secret'));
    }

    public function syncAll(): void
    {
        $this->taxRateId();
        Plan::where('is_free', false)->each(fn (Plan $plan) => $this->syncPlan($plan));
        NamePriceTier::query()->each(fn (NamePriceTier $tier) => $this->syncTier($tier));
        PlanOffer::with('plan')->each(fn (PlanOffer $offer) => $this->syncOffer($offer));
        Cache::forget($this->portalCacheKey());
        $this->portalConfigurationId(refresh: true);
    }

    public function syncPlan(Plan $plan): void
    {
        if ($plan->is_free) {
            return;
        }
        $stripe = $this->stripe();
        $product = [
            'name' => $plan->name,
            'description' => $plan->description ?: null,
            'active' => (bool) $plan->is_active,
            'metadata' => ['plan_id' => $plan->id, 'slug' => $plan->slug],
        ];

        if (! $plan->stripe_product_id) {
            $plan->stripe_product_id = $stripe->products->create($product)->id;
        } else {
            $stripe->products->update($plan->stripe_product_id, $product);
        }

        $current = $plan->stripe_price_id ? $stripe->prices->retrieve($plan->stripe_price_id) : null;
        if (! $current || $current->unit_amount !== $plan->price_cents || $current->recurring?->interval !== $plan->interval) {
            $price = $stripe->prices->create([
                'product' => $plan->stripe_product_id,
                'currency' => strtolower($plan->currency),
                'unit_amount' => $plan->price_cents,
                'recurring' => ['interval' => $plan->interval],
                'tax_behavior' => 'inclusive',
                'metadata' => ['plan_id' => $plan->id],
            ]);
            if ($current) {
                $stripe->prices->update($current->id, ['active' => false]);
            }
            $plan->stripe_price_id = $price->id;
            PlanPrice::create(['plan_id' => $plan->id, 'stripe_price_id' => $price->id,
                'price_cents' => $plan->price_cents, 'interval' => $plan->interval]);
            $stripe->products->update($plan->stripe_product_id, ['default_price' => $price->id]);
        }

        $plan->saveQuietly();
    }

    public function syncTier(NamePriceTier $tier): void
    {
        $stripe = $this->stripe();
        $name = "Nombre corto ({$tier->min_length}–{$tier->max_length} caracteres)";

        if (! $tier->stripe_product_id) {
            $tier->stripe_product_id = $stripe->products->create(['name' => $name, 'metadata' => ['name_price_tier_id' => $tier->id]])->id;
        } else {
            $stripe->products->update($tier->stripe_product_id, ['name' => $name, 'active' => (bool) $tier->is_active]);
        }

        foreach (['stripe_price_id' => ['month', $tier->price_cents], 'stripe_price_year_id' => ['year', $tier->price_cents * 12]] as $column => [$interval, $amount]) {
            $current = $tier->{$column} ? $stripe->prices->retrieve($tier->{$column}) : null;
            if (! $current || $current->unit_amount !== $amount) {
                $tier->{$column} = $stripe->prices->create([
                    'product' => $tier->stripe_product_id, 'currency' => 'eur', 'unit_amount' => $amount,
                    'recurring' => ['interval' => $interval], 'tax_behavior' => 'inclusive',
                    'metadata' => ['name_price_tier_id' => $tier->id],
                ])->id;
                if ($current) {
                    $stripe->prices->update($current->id, ['active' => false]);
                }
            }
        }

        $tier->saveQuietly();
    }

    public function syncOffer(PlanOffer $offer): void
    {
        $plan = $offer->plan;
        if (! $plan || $plan->is_free || ! $plan->stripe_product_id) {
            return;
        }
        $stripe = $this->stripe();
        $terms = array_filter([
            'percent_off' => $offer->type === 'percent' ? min($offer->value, 100) : null,
            'amount_off' => $offer->type === 'amount' ? $offer->value : null,
            'currency' => $offer->type === 'amount' ? 'eur' : null,
            'duration' => $offer->duration,
            'duration_in_months' => $offer->duration === 'repeating' ? ($offer->duration_months ?: 1) : null,
            'max_redemptions' => $offer->max_redemptions,
            'applies_to' => ['products' => [$plan->stripe_product_id]],
        ], fn ($v) => $v !== null);
        $signature = md5(json_encode($terms));

        $current = $offer->stripe_coupon_id ? $stripe->coupons->retrieve($offer->stripe_coupon_id) : null;
        if (! $current || ($current->metadata['signature'] ?? null) !== $signature) {
            $offer->stripe_coupon_id = $stripe->coupons->create($terms + [
                'name' => mb_substr($offer->label, 0, 40),
                'metadata' => ['plan_offer_id' => $offer->id, 'signature' => $signature],
            ])->id;
            if ($current) {
                $stripe->coupons->delete($current->id);   // los clientes que ya lo usan lo conservan
            }
        } elseif ($current->name !== mb_substr($offer->label, 0, 40)) {
            $stripe->coupons->update($current->id, ['name' => mb_substr($offer->label, 0, 40)]);
        }

        $offer->saveQuietly();
    }

    /** El Tax Rate del IVA (21 %, incluido en el precio). Se busca por metadato y se crea si no existe. */
    public function taxRateId(): string
    {
        return Cache::rememberForever('stripe:tax-rate:'.self::TAX_RATE_KEY.':'.substr(md5((string) config('cashier.secret')), 0, 8), function () {
            $stripe = $this->stripe();
            foreach ($stripe->taxRates->all(['active' => true, 'limit' => 100])->autoPagingIterator() as $rate) {
                if (($rate->metadata['key'] ?? null) === self::TAX_RATE_KEY) {
                    return $rate->id;
                }
            }

            return $stripe->taxRates->create([
                'display_name' => 'IVA', 'description' => 'IVA 21 % (España)', 'jurisdiction' => 'ES',
                'country' => 'ES', 'percentage' => 21, 'inclusive' => true, 'tax_type' => 'vat',
                'metadata' => ['key' => self::TAX_RATE_KEY],
            ])->id;
        });
    }

    /**
     * Configuración del portal de cliente de Stripe: tarjeta, facturas y cancelar al final del periodo.
     * Cambiar de plan **no**: se hace en Mi cuenta → "Cambiar de plan" (App\Services\PlanChange), que
     * también sabe del sobrecoste de nombre corto. Si alguien cambiara de plan en Stripe por otra vía, el
     * webhook customer.subscription.updated lo recoge igual (MailboxBilling::sync).
     */
    public function portalConfigurationId(bool $refresh = false): string
    {
        $build = function () {
            $stripe = $this->stripe();
            $params = [
                'business_profile' => [
                    'headline' => 'Tu tarjeta y tus facturas de unagrandeylibre.es',   // máx. 60 caracteres
                    'privacy_policy_url' => route('legal', 'privacidad'),
                    'terms_of_service_url' => route('legal', 'condiciones'),
                ],
                'default_return_url' => route('account'),
                'features' => [
                    'customer_update' => ['enabled' => true, 'allowed_updates' => ['address', 'name', 'tax_id']],
                    'invoice_history' => ['enabled' => true],
                    'payment_method_update' => ['enabled' => true],
                    'subscription_cancel' => ['enabled' => true, 'mode' => 'at_period_end',
                        'cancellation_reason' => ['enabled' => true, 'options' => ['too_expensive', 'missing_features', 'switched_service', 'unused', 'other']]],
                    'subscription_update' => ['enabled' => false],
                ],
                'metadata' => ['key' => 'portal-unagrandeylibre'],
            ];

            foreach ($stripe->billingPortal->configurations->all(['limit' => 100])->autoPagingIterator() as $config) {
                if (($config->metadata['key'] ?? null) === 'portal-unagrandeylibre') {
                    $stripe->billingPortal->configurations->update($config->id, $params);

                    return $config->id;
                }
            }

            return $stripe->billingPortal->configurations->create($params)->id;
        };

        if ($refresh) {
            $id = $build();
            Cache::forever($this->portalCacheKey(), $id);

            return $id;
        }

        return Cache::rememberForever($this->portalCacheKey(), $build);
    }

    private function portalCacheKey(): string
    {
        return 'stripe:portal-config:'.substr(md5((string) config('cashier.secret')), 0, 8);
    }

    private function stripe(): StripeClient
    {
        return Cashier::stripe();
    }
}
