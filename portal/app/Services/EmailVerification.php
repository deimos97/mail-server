<?php

namespace App\Services;

use App\Mail\VerifyRecoveryEmail;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Verificación del email de recuperación: un único correo con enlace y código de 6 cifras (sirve
 * aunque lo abra en otro dispositivo). Al verificar, sus buzones pueden enviar (can_send).
 */
class EmailVerification
{
    public const MINUTES = 60;

    public const MAX_ATTEMPTS = 5;

    public function send(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($user), ['hash' => hash('sha256', $code.$user->email), 'attempts' => 0], now()->addMinutes(self::MINUTES));

        $link = URL::temporarySignedRoute('signup.verify.link', now()->addMinutes(self::MINUTES), [
            'user' => $user->id,
            'hash' => sha1($user->email),
        ]);

        Mail::to($user->email)->queue(new VerifyRecoveryEmail($code, $link));
    }

    /** true si el código es correcto. Tras MAX_ATTEMPTS fallos, el código deja de valer. */
    public function verifyCode(User $user, string $code): bool
    {
        $entry = Cache::get($this->key($user));
        if (! $entry || $entry['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! hash_equals($entry['hash'], hash('sha256', preg_replace('/\D/', '', $code).$user->email))) {
            $entry['attempts']++;
            Cache::put($this->key($user), $entry, now()->addMinutes(self::MINUTES));

            return false;
        }

        $this->markVerified($user);

        return true;
    }

    public function codeExhausted(User $user): bool
    {
        $entry = Cache::get($this->key($user));

        return ! $entry || $entry['attempts'] >= self::MAX_ATTEMPTS;
    }

    public function markVerified(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
        Cache::forget($this->key($user));
        Mailbox::where('user_id', $user->id)->update(['can_send' => true]);
    }

    private function key(User $user): string
    {
        return "email-verification:{$user->id}";
    }
}
