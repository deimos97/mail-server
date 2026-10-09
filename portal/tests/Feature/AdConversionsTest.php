<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdConversions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdConversionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1]),
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok']),
            'googleads.googleapis.com/*' => Http::response(['results' => []]),
        ]);
        config([
            'ads.meta' => ['pixel_id' => '123', 'access_token' => 'secreto', 'test_event_code' => null, 'api_version' => 'v21.0'],
            'ads.google' => ['customer_id' => '1112223333', 'login_customer_id' => null, 'developer_token' => 'dev', 'client_id' => 'c',
                'client_secret' => 's', 'refresh_token' => 'r', 'api_version' => 'v21', 'signup_action' => '55', 'purchase_action' => '66'],
        ]);
    }

    private function user(array $attribution): User
    {
        $user = User::factory()->create(['email' => User::where('email', 'Ana@Gmail.com')->exists() ? fake()->unique()->safeEmail() : 'Ana@Gmail.com']);
        $user->forceFill(['attribution' => $attribution, 'signup_ip' => '203.0.113.5'])->save();

        return $user;
    }

    public function test_meta_gets_the_signup_with_hashed_email_only_with_consent_and_fbclid(): void
    {
        app(AdConversions::class)->signup($this->user(['ads_consent' => true, 'fbclid' => 'abc', 'user_agent' => 'UA']));

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), 'graph.facebook.com/v21.0/123/events')) {
                return false;
            }
            $event = $request['data'][0];

            return $event['event_name'] === 'CompleteRegistration'
                && $event['user_data']['em'] === [hash('sha256', 'ana@gmail.com')]
                && str_ends_with($event['user_data']['fbc'], '.abc');
        });
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'googleads'));
    }

    public function test_google_gets_the_purchase_with_gclid_and_value(): void
    {
        app(AdConversions::class)->purchase($this->user(['ads_consent' => true, 'gclid' => 'G-1']), 199);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'customers/1112223333:uploadClickConversions')
            && $r['conversions'][0]['gclid'] === 'G-1'
            && $r['conversions'][0]['conversionValue'] === 1.99
            && str_ends_with($r['conversions'][0]['conversionAction'], '/conversionActions/66')
            && $r->header('developer-token') === ['dev']);
    }

    public function test_nothing_without_consent_or_without_configuration(): void
    {
        app(AdConversions::class)->signup($this->user(['fbclid' => 'abc', 'gclid' => 'G-1']));       // sin consentimiento
        config(['ads.meta.pixel_id' => null, 'ads.google.customer_id' => null]);
        app(AdConversions::class)->signup($this->user(['ads_consent' => true, 'fbclid' => 'abc', 'gclid' => 'G-1']));

        Http::assertNothingSent();
    }
}
