<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Cloudflare Turnstile: comprueba en servidor el token del widget antibots. */
class Turnstile
{
    public function passes(?string $token, ?string $ip): bool
    {
        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $response->json('success');
        } catch (Throwable $e) {
            // Si Cloudflare no responde, mejor dejar pasar (hay más límites detrás) que bloquear altas
            Log::warning('Turnstile no responde', ['error' => $e->getMessage()]);

            return true;
        }
    }
}
