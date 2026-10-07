<?php

namespace App\Services;

use App\Http\Middleware\CaptureAttribution;
use App\Jobs\CapturePostHogEvent;
use App\Models\Mailbox;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Eventos de producto que se miden en el servidor (no dependen de que cargue el JS ni de los adblockers).
 *
 * - Con consentimiento (`ugl_consent=accepted`): el evento lleva el distinct_id del navegador (cookie de PostHog),
 *   así cierra el mismo embudo que los eventos del front, y la atribución completa de la campaña.
 * - Sin consentimiento: se cuenta igual, pero anónimo: id aleatorio, sin perfil de persona, sin click IDs
 *   ni referrer completo. Nada que permita reconocer a la persona.
 * - Nunca se envían la dirección, el email ni la IP del usuario (geoip desactivado: la IP sería la del servidor).
 */
class ServerAnalytics
{
    public const CONSENT_COOKIE = 'ugl_consent';

    private const CAMPAIGN = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    private const CLICK_IDS = ['gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ttclid', 'twclid', 'li_fat_id'];

    /** Nombre de la cookie en la que posthog-js guarda su estado (con el distinct_id). */
    public static function posthogCookie(): ?string
    {
        $key = config('services.posthog.key');

        return $key ? 'ph_'.$key.'_posthog' : null;
    }

    public function signupCompleted(Request $request, Mailbox $mailbox, Plan $plan): void
    {
        $this->capture($request, 'signup_completed', [
            'plan' => $plan->slug,
            'plan_free' => (bool) $plan->is_free,
            'domain' => $mailbox->domain->name,
            'email_verified' => (bool) $mailbox->can_send,
        ]);
    }

    private function capture(Request $request, string $event, array $properties): void
    {
        if (! config('services.posthog.key')) {
            return;
        }

        $consented = $request->cookie(self::CONSENT_COOKIE) === 'accepted';
        $browserId = $consented ? $this->browserDistinctId($request) : null;
        // La del usuario (guardada al crear la cuenta) o, si no hay, la de la sesión
        $attribution = (array) ($request->user()?->attribution ?? $request->session()->get(CaptureAttribution::SESSION_KEY, []));

        $keep = $consented ? [...self::CAMPAIGN, ...self::CLICK_IDS] : self::CAMPAIGN;
        $campaign = array_filter(array_intersect_key($attribution, array_flip($keep)));

        $referrer = $attribution['referrer'] ?? null;
        if ($referrer && ! $consented) {
            $referrer = parse_url($referrer, PHP_URL_HOST) ?: null;
        }

        CapturePostHogEvent::dispatch([
            'event' => $event,
            'distinct_id' => $browserId ?? (string) Str::uuid7(),
            'uuid' => (string) Str::uuid7(),
            'timestamp' => now()->toIso8601String(),
            'properties' => [
                ...$properties,
                ...$campaign,
                'source_referrer' => $referrer,
                'consent' => $consented,
                '$process_person_profile' => $browserId !== null,
                '$geoip_disable' => true,
                '$lib' => 'portal',
            ],
        ]);
    }

    private function browserDistinctId(Request $request): ?string
    {
        $cookie = self::posthogCookie();
        $state = $cookie ? json_decode((string) $request->cookie($cookie), true) : null;
        $id = $state['distinct_id'] ?? null;

        return is_string($id) && $id !== '' && strlen($id) <= 200 ? $id : null;
    }
}
