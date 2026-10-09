<?php

namespace App\Services;

use App\Jobs\SendAdConversion;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Conversiones de servidor (alta y primer pago) para Meta y Google Ads. Se envían solo si:
 * - la plataforma está configurada (config/ads.php; en blanco hasta la Fase 6),
 * - el usuario aceptó las cookies al darse de alta (`users.attribution.ads_consent`),
 * - y llegó desde un anuncio de esa plataforma (fbclid para Meta; gclid, gbraid o wbraid para Google).
 * A Meta se le manda el email de recuperación cifrado con SHA-256 (nunca en claro), como pide su API.
 */
class AdConversions
{
    public function signup(User $user): void
    {
        $this->send($user, 'signup');
    }

    public function purchase(User $user, int $valueCents): void
    {
        $this->send($user, 'purchase', $valueCents);
    }

    private function send(User $user, string $event, ?int $valueCents = null): void
    {
        $a = (array) ($user->attribution ?? []);
        if (! ($a['ads_consent'] ?? false)) {
            return;
        }
        $eventId = $event.'-'.$user->id.'-'.Str::random(8);

        if (filled($a['fbclid'] ?? null) && filled(config('ads.meta.pixel_id')) && filled(config('ads.meta.access_token'))) {
            SendAdConversion::dispatch('meta', $this->meta($user, $a, $event, $valueCents, $eventId));
        }

        $click = collect(['gclid', 'gbraid', 'wbraid'])->first(fn ($k) => filled($a[$k] ?? null));
        $action = config($event === 'signup' ? 'ads.google.signup_action' : 'ads.google.purchase_action');
        if ($click && filled(config('ads.google.customer_id')) && filled(config('ads.google.developer_token')) && filled($action)) {
            SendAdConversion::dispatch('google', $this->google($a, $click, $action, $valueCents, $eventId));
        }
    }

    private function meta(User $user, array $a, string $event, ?int $valueCents, string $eventId): array
    {
        $firstSeen = isset($a['first_seen_at']) ? strtotime($a['first_seen_at']) : time();

        return array_filter([
            'data' => [array_filter([
                'event_name' => $event === 'signup' ? 'CompleteRegistration' : 'Subscribe',
                'event_time' => time(),
                'event_id' => $eventId,
                'action_source' => 'website',
                'event_source_url' => url('/alta'),
                'user_data' => array_filter([
                    'em' => [hash('sha256', mb_strtolower(trim($user->email)))],
                    'fbc' => 'fb.1.'.($firstSeen * 1000).'.'.$a['fbclid'],
                    'client_ip_address' => $user->signup_ip,
                    'client_user_agent' => $a['user_agent'] ?? null,
                ]),
                'custom_data' => $valueCents !== null ? ['currency' => 'EUR', 'value' => round($valueCents / 100, 2)] : null,
            ])],
            'test_event_code' => config('ads.meta.test_event_code'),
        ]);
    }

    private function google(array $a, string $clickKey, string $action, ?int $valueCents, string $eventId): array
    {
        return [
            'conversions' => [array_filter([
                $clickKey => $a[$clickKey],
                'conversionAction' => 'customers/'.config('ads.google.customer_id').'/conversionActions/'.$action,
                'conversionDateTime' => now()->utc()->format('Y-m-d H:i:sP'),
                'conversionValue' => $valueCents !== null ? round($valueCents / 100, 2) : null,
                'currencyCode' => $valueCents !== null ? 'EUR' : null,
                'orderId' => $eventId,
                'consent' => ['adUserData' => 'GRANTED', 'adPersonalization' => 'GRANTED'],
            ], fn ($v) => $v !== null)],
            'partialFailure' => true,
        ];
    }
}
