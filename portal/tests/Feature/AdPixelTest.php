<?php

namespace Tests\Feature;

use App\Services\AdPixel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class AdPixelTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed(\Database\Seeders\DemoPlansSeeder::class);
    }

    public function test_off_outside_production_by_default(): void
    {
        $this->assertFalse(app(AdPixel::class)->enabled());
        $this->get('/')->assertDontSee('"pixel":"rhe58"', false);
    }

    public function test_events_reach_the_next_page_once_and_only_as_config_for_the_consent_gated_js(): void
    {
        config(['ads.x.browser_pixel' => true]);
        $this->get('/')->assertSee('"pixel":"rhe58","events":[]', false);

        $this->withSession([])->get('/');
        app(AdPixel::class)->signup();
        app(AdPixel::class)->purchase(199, 'checkout-7');
        $events = app(AdPixel::class)->forPage()['events'];
        $this->assertSame('tw-rhe58-rhmtd', $events[0][0]);
        $this->assertSame('tw-rhe58-rhmte', $events[1][0]);
        $this->assertSame(['value' => 1.99, 'currency' => 'EUR', 'conversion_id' => 'checkout-7'], (array) $events[1][1]);
        $this->assertSame([], app(AdPixel::class)->forPage()['events']);   // una sola vez
    }

    public function test_cookie_texts_mention_x(): void
    {
        $this->get('/legal/cookies')->assertSee('ugl_consent2')->assertSee('anuncios en X');
        $this->get('/legal/privacidad')->assertSee('Twitter International Unlimited Company');
    }
}
