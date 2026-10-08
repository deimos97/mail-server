<?php

namespace Tests\Feature;

use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\PlanOffer;
use App\Models\PlanPrice;
use App\Services\StripeCatalog;
use Database\Seeders\DemoPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeStripe;
use Tests\TestCase;

class StripeCatalogTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoPlansSeeder::class);
        $this->stripe = FakeStripe::install();
    }

    protected function tearDown(): void
    {
        FakeStripe::uninstall();
        parent::tearDown();
    }

    public function test_paid_plans_get_a_product_and_an_inclusive_price(): void
    {
        app(StripeCatalog::class)->syncAll();

        $plan = Plan::where('is_free', false)->firstOrFail();
        $this->assertNotNull($plan->stripe_product_id);
        $price = $this->stripe->objects['prices'][$plan->stripe_price_id];
        $this->assertSame($plan->price_cents, $price['unit_amount']);
        $this->assertSame('inclusive', $price['tax_behavior']);
        $this->assertSame($plan->interval, $price['recurring']['interval']);
        $this->assertNull(Plan::where('is_free', true)->first()->stripe_product_id);   // el gratis no va a Stripe

        $rate = array_values($this->stripe->objects['tax_rates'])[0];
        $this->assertSame([21, true, 'ES'], [$rate['percentage'], $rate['inclusive'], $rate['country']]);
    }

    public function test_changing_the_price_creates_a_new_price_and_keeps_the_old_one_mapped(): void
    {
        app(StripeCatalog::class)->syncAll();
        $plan = Plan::where('is_free', false)->firstOrFail();
        $old = $plan->stripe_price_id;

        $plan->update(['price_cents' => $plan->price_cents + 100]);   // el admin guarda → se sincroniza
        $plan->refresh();

        $this->assertNotSame($old, $plan->stripe_price_id);
        $this->assertFalse($this->stripe->objects['prices'][$old]['active']);         // el viejo, archivado
        $this->assertTrue(PlanPrice::planFor($old)->is($plan));                       // pero sigue siendo de este plan
        $this->assertTrue(PlanPrice::planFor($plan->stripe_price_id)->is($plan));

        // Guardar sin cambiar el precio no crea otro
        $before = count($this->stripe->sent('POST', 'prices'));
        $plan->update(['name' => 'Otro nombre']);
        $this->assertCount($before, $this->stripe->sent('POST', 'prices'));
    }

    public function test_short_name_tiers_get_monthly_and_yearly_prices(): void
    {
        $tier = NamePriceTier::create(['min_length' => 1, 'max_length' => 2, 'price_cents' => 500]);
        app(StripeCatalog::class)->syncTier($tier->fresh());
        $tier->refresh();

        $this->assertSame(500, $this->stripe->objects['prices'][$tier->stripe_price_id]['unit_amount']);
        $this->assertSame(6000, $this->stripe->objects['prices'][$tier->stripe_price_year_id]['unit_amount']);
    }

    public function test_offers_become_coupons_and_changing_terms_replaces_the_coupon(): void
    {
        app(StripeCatalog::class)->syncAll();
        $plan = Plan::where('is_free', false)->firstOrFail();
        $offer = PlanOffer::create(['plan_id' => $plan->id, 'label' => '-50 % lanzamiento', 'type' => 'percent',
            'value' => 50, 'duration' => 'repeating', 'duration_months' => 3]);
        $offer->refresh();

        $coupon = $this->stripe->objects['coupons'][$offer->stripe_coupon_id];
        $this->assertSame([50, 'repeating', 3], [$coupon['percent_off'], $coupon['duration'], $coupon['duration_in_months']]);
        $this->assertSame([$plan->stripe_product_id], $coupon['applies_to']['products']);

        $old = $offer->stripe_coupon_id;
        $offer->update(['value' => 30]);
        $this->assertNotSame($old, $offer->fresh()->stripe_coupon_id);
        $this->assertArrayNotHasKey($old, $this->stripe->objects['coupons'] ?? []);

        // Solo el texto: mismo cupón
        $current = $offer->fresh()->stripe_coupon_id;
        $offer->update(['label' => '-30 % otoño']);
        $this->assertSame($current, $offer->fresh()->stripe_coupon_id);
    }
}
