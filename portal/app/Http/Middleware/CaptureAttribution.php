<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarda en la sesión de dónde viene el visitante (primer contacto: no se sobrescribe).
 * En la Fase 2 se copia al usuario al darse de alta, para saber qué campaña trae altas de pago.
 * Enviarlo a Google/Meta (conversiones) solo se hará con consentimiento.
 */
class CaptureAttribution
{
    public const SESSION_KEY = 'attribution';

    private const PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ttclid', 'twclid', 'li_fat_id',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->session()->has(self::SESSION_KEY)) {
            $params = collect($request->only(self::PARAMS))
                ->filter(fn ($value) => is_string($value) && $value !== '')
                ->map(fn (string $value) => mb_substr($value, 0, 255));

            $referrer = $request->headers->get('referer');
            $external = $referrer && parse_url($referrer, PHP_URL_HOST) !== $request->getHost();

            if ($params->isNotEmpty() || $external) {
                $request->session()->put(self::SESSION_KEY, [
                    ...$params->all(),
                    'referrer' => $external ? mb_substr($referrer, 0, 500) : null,
                    'landing_path' => mb_substr($request->getRequestUri(), 0, 500),
                    'first_seen_at' => now()->toIso8601String(),
                ]);
            }
        }

        return $next($request);
    }
}
