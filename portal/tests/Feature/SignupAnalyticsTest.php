<?php

namespace Tests\Feature;

use Database\Seeders\DemoPlansSeeder;
use Database\Seeders\NameRulesSeeder;
use Database\Seeders\ReservedNamesSeeder;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsesMailserverDatabase;
use Tests\TestCase;

class SignupAnalyticsTest extends TestCase
{
    use RefreshDatabase, UsesMailserverDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMailserverDatabase();
        $this->seed([ReservedNamesSeeder::class, NameRulesSeeder::class, DemoPlansSeeder::class]);
        config(['services.posthog.key' => 'phc_prueba', 'services.posthog.host' => 'https://eu.i.posthog.com']);
        EncryptCookies::except('ph_phc_prueba_posthog'); // en producción lo hace AppServiceProvider con la clave del .env
        Mail::fake();
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
            'api.pwnedpasswords.com/*' => Http::response(''),
            'eu.i.posthog.com/*' => Http::response(['status' => 'Ok']),
        ]);
    }

    private function signup(): void
    {
        $this->get('/alta?nombre=lucia&utm_source=meta&utm_campaign=otono&fbclid=clic123')->assertRedirect('/alta/cuenta');
        $this->post('/alta/cuenta', ['email' => 'lucia@gmail.com', 'password' => 'una-clave-larga-123', 'terms' => '1', 'cf-turnstile-response' => 'ok']);
        $this->post('/alta/plan', ['plan' => 'gratis'])->assertRedirect('/alta/verificar');
    }

    private function sentEvent(): array
    {
        $events = Http::recorded(fn (Request $request) => str_contains($request->url(), 'posthog.com'));
        $this->assertCount(1, $events);
        [$request] = $events->first();
        $this->assertSame('https://eu.i.posthog.com/i/v0/e/', $request->url());
        $this->assertSame('phc_prueba', $request['api_key']);
        $this->assertSame('signup_completed', $request['event']);

        return $request->data();
    }

    public function test_with_consent_it_joins_the_browser_funnel(): void
    {
        $this->withUnencryptedCookie('ugl_consent', 'accepted')
            ->withUnencryptedCookie('ph_phc_prueba_posthog', json_encode(['distinct_id' => 'navegador-1']));
        $this->signup();

        $event = $this->sentEvent();
        $this->assertSame('navegador-1', $event['distinct_id']);
        $this->assertTrue($event['properties']['$process_person_profile']);
        $this->assertSame('clic123', $event['properties']['fbclid']);
        $this->assertSame(['gratis', true, 'meta', 'otono'], [$event['properties']['plan'], $event['properties']['consent'],
            $event['properties']['utm_source'], $event['properties']['utm_campaign']]);
    }

    public function test_without_consent_it_is_anonymous(): void
    {
        $this->withUnencryptedCookie('ph_phc_prueba_posthog', json_encode(['distinct_id' => 'navegador-1']));
        $this->signup();

        $event = $this->sentEvent();
        $this->assertNotSame('navegador-1', $event['distinct_id']);
        $this->assertFalse($event['properties']['consent']);
        $this->assertFalse($event['properties']['$process_person_profile']);
        $this->assertTrue($event['properties']['$geoip_disable']);
        $this->assertSame('meta', $event['properties']['utm_source']);
        $this->assertArrayNotHasKey('fbclid', $event['properties']);
        $this->assertStringNotContainsString('lucia', json_encode($event));
    }

    public function test_nothing_is_sent_without_a_posthog_key(): void
    {
        config(['services.posthog.key' => null]);
        $this->signup();

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'posthog.com'));
    }
}
