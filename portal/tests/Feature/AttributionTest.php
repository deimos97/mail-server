<?php

namespace Tests\Feature;

use App\Http\Middleware\CaptureAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class AttributionTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
    }

    public function test_first_touch_campaign_is_kept_in_the_session(): void
    {
        $this->get('/?utm_source=meta&utm_campaign=lanzamiento&fbclid=abc123&otro=x')
            ->assertSessionHas(CaptureAttribution::SESSION_KEY, fn (array $a) => $a['utm_source'] === 'meta'
                && $a['utm_campaign'] === 'lanzamiento'
                && $a['fbclid'] === 'abc123'
                && ! isset($a['otro'])
                && str_starts_with($a['landing_path'], '/?utm_source=meta'));

        // Una visita posterior desde otra campaña no pisa la primera
        $this->get('/?utm_source=google&gclid=zzz')
            ->assertSessionHas(CaptureAttribution::SESSION_KEY, fn (array $a) => $a['utm_source'] === 'meta' && ! isset($a['gclid']));
    }

    public function test_external_referrer_without_utms_is_recorded(): void
    {
        $this->withHeader('Referer', 'https://www.google.com/')
            ->get('/')
            ->assertSessionHas(CaptureAttribution::SESSION_KEY, fn (array $a) => $a['referrer'] === 'https://www.google.com/');
    }

    public function test_direct_and_internal_visits_record_nothing(): void
    {
        $this->get('/')->assertSessionMissing(CaptureAttribution::SESSION_KEY);
        $this->withHeader('Referer', 'http://localhost/legal/cookies')->get('/')->assertSessionMissing(CaptureAttribution::SESSION_KEY);
    }

    public function test_cookie_banner_is_rendered_and_posthog_only_with_a_key(): void
    {
        config(['services.posthog.key' => null]);
        $this->get('/')->assertSee('x-data="consentBanner"', false)->assertDontSee('window.UGL', false);

        config(['services.posthog.key' => 'phc_test']);
        $this->get('/')->assertSee('window.UGL = { posthog: { key: \'phc_test\' }, experiments: {} }', false);
    }
}
