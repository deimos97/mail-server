<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/** Envía una conversión a Meta (Conversions API) o a Google Ads (uploadClickConversions), por la cola. */
class SendAdConversion implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $platform, public array $payload) {}

    public function backoff(): array
    {
        return [30, 120, 600, 3600];
    }

    public function handle(): void
    {
        $this->platform === 'meta' ? $this->meta() : $this->google();
    }

    private function meta(): void
    {
        $c = config('ads.meta');
        Http::timeout(15)->acceptJson()
            ->post("https://graph.facebook.com/{$c['api_version']}/{$c['pixel_id']}/events?access_token=".urlencode($c['access_token']), $this->payload)
            ->throw();
    }

    private function google(): void
    {
        $c = config('ads.google');
        $token = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token', 'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'], 'refresh_token' => $c['refresh_token'],
        ])->throw()->json('access_token');

        Http::timeout(15)->acceptJson()->withToken($token)
            ->withHeaders(array_filter(['developer-token' => $c['developer_token'], 'login-customer-id' => $c['login_customer_id']]))
            ->post("https://googleads.googleapis.com/{$c['api_version']}/customers/{$c['customer_id']}:uploadClickConversions", $this->payload)
            ->throw();
    }
}
