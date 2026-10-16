<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/** Envía una conversión a Meta (Conversions API), Google Ads (uploadClickConversions) o X (Conversions API), por la cola. */
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
        match ($this->platform) {
            'meta' => $this->meta(),
            'google' => $this->google(),
            'x' => $this->x(),
        };
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

    /** X Ads API: OAuth 1.0a (HMAC-SHA1). Con cuerpo JSON, la firma solo lleva los parámetros oauth_*. */
    private function x(): void
    {
        $c = config('ads.x');
        $url = "https://ads-api.x.com/{$c['api_version']}/measurement/conversions/{$c['pixel_id']}";
        $oauth = [
            'oauth_consumer_key' => $c['consumer_key'],
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $c['access_token'],
            'oauth_version' => '1.0',
        ];
        ksort($oauth);
        $base = 'POST&'.rawurlencode($url).'&'.rawurlencode(http_build_query($oauth, '', '&', PHP_QUERY_RFC3986));
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $base,
            rawurlencode($c['consumer_secret']).'&'.rawurlencode($c['access_token_secret']), true));
        $header = 'OAuth '.collect($oauth)->map(fn ($v, $k) => $k.'="'.rawurlencode($v).'"')->implode(', ');

        Http::timeout(15)->acceptJson()->withHeaders(['Authorization' => $header])->post($url, $this->payload)->throw();
    }
}
