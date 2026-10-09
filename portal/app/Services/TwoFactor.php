<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;

/**
 * Verificación en dos pasos de los clientes con una app (Google Authenticator, 1Password, Authy…): códigos
 * TOTP de 6 cifras. Un código ya usado no vale otra vez (anti-repetición) y hay 8 códigos de recuperación
 * de un solo uso por si se pierde el móvil.
 */
class TwoFactor
{
    public const RECOVERY_CODES = 8;

    public function __construct(private Google2FA $google2fa) {}

    public function newSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /** QR (data URI de un SVG) para escanear con la app. */
    public function qr(User $user, string $secret): string
    {
        return (new Google2FAQRCode)->getQRCodeInline('unagrandeylibre.es', $user->email, $secret);
    }

    /** Código de la app contra un secreto (±30 s). Cada código solo vale una vez por usuario. */
    public function verify(string $secret, string $code, ?int $userId = null): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        $key = 'two-factor:last:'.($userId ?? md5($secret));
        $timestamp = $this->google2fa->verifyKeyNewer($secret, $code, Cache::get($key), 1);
        if ($timestamp === false) {
            return false;
        }
        Cache::put($key, $timestamp === true ? $this->google2fa->getTimestamp() : $timestamp, now()->addMinutes(5));

        return true;
    }

    /** Código de la app o, si no, uno de recuperación (que se gasta). */
    public function verifyLogin(User $user, string $code): bool
    {
        if ($this->verify($user->two_factor_secret, $code, $user->id)) {
            return true;
        }

        $normalized = mb_strtolower(preg_replace('/[^a-z0-9]/i', '', $code));
        $codes = collect($user->two_factor_recovery_codes ?? []);
        $match = $codes->first(fn ($c) => hash_equals(str_replace('-', '', $c), $normalized));
        if (! $match) {
            return false;
        }
        $user->forceFill(['two_factor_recovery_codes' => $codes->reject(fn ($c) => $c === $match)->values()->all()])->save();

        return true;
    }

    /** @return list<string> */
    public function newRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODES))
            ->map(fn () => mb_strtolower(Str::random(5).'-'.Str::random(5)))->all();
    }

    public function enable(User $user, string $secret): array
    {
        $codes = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $codes, 'two_factor_confirmed_at' => now()])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    }
}
