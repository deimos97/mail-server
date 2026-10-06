<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPricingTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $attributes = []): Plan
    {
        return Plan::create($attributes + [
            'slug' => 'p'.uniqid(), 'name' => 'Plan', 'price_cents' => 500,
            'quota_bytes' => 1024 ** 3, 'send_limit_per_hour' => 50, 'tier' => 'basic',
        ]);
    }

    public function test_only_active_plans_inside_their_window_are_visible(): void
    {
        $this->travelTo('2026-10-06 12:00:00');

        $always = $this->plan();
        $this->plan(['is_active' => false]);
        $this->plan(['available_from' => '2026-10-07 00:00:00']);
        $this->plan(['available_until' => '2026-10-06 12:00:00']);   // el límite es exclusivo
        $window = $this->plan(['available_from' => '2026-10-01', 'available_until' => '2026-10-31']);

        $this->assertEqualsCanonicalizing([$always->id, $window->id], Plan::visible()->pluck('id')->all());
        $this->assertTrue($window->isVisible());
        $this->assertFalse($window->isVisible(now()->setDate(2026, 11, 1)));
    }

    public function test_running_offer_is_applied_to_the_price(): void
    {
        $this->travelTo('2026-10-06 12:00:00');
        $plan = $this->plan(['price_cents' => 199]);
        $plan->offers()->create(['label' => '-50 %', 'type' => 'percent', 'value' => 50,
            'starts_at' => '2026-10-01', 'ends_at' => '2026-10-31']);

        $this->assertSame(100, $plan->effectivePriceCents());          // 99,5 redondea a 100
        $this->assertSame(199, $plan->effectivePriceCents(now()->setDate(2026, 11, 1)));
    }

    public function test_inactive_or_future_offers_are_ignored(): void
    {
        $this->travelTo('2026-10-06 12:00:00');
        $plan = $this->plan();
        $plan->offers()->create(['label' => 'off', 'type' => 'percent', 'value' => 50, 'is_active' => false]);
        $plan->offers()->create(['label' => 'luego', 'type' => 'percent', 'value' => 50, 'starts_at' => '2026-12-01']);

        $this->assertNull($plan->currentOffer());
        $this->assertSame(500, $plan->effectivePriceCents());
    }

    public function test_overlapping_offers_use_the_biggest_discount(): void
    {
        $plan = $this->plan(['price_cents' => 500]);
        $plan->offers()->create(['label' => '-10 %', 'type' => 'percent', 'value' => 10]);
        $plan->offers()->create(['label' => '-2 €', 'type' => 'amount', 'value' => 200]);

        $this->assertSame('-2 €', $plan->currentOffer()->label);
        $this->assertSame(300, $plan->effectivePriceCents());
    }

    public function test_amount_discount_never_goes_below_zero(): void
    {
        $plan = $this->plan(['price_cents' => 150]);
        $plan->offers()->create(['label' => 'regalo', 'type' => 'amount', 'value' => 500]);

        $this->assertSame(0, $plan->effectivePriceCents());
    }

    public function test_free_plans_ignore_offers(): void
    {
        $plan = $this->plan(['is_free' => true, 'price_cents' => 0]);
        $plan->offers()->create(['label' => 'x', 'type' => 'amount', 'value' => 100]);

        $this->assertNull($plan->currentOffer());
        $this->assertSame(0, $plan->effectivePriceCents());
    }
}
