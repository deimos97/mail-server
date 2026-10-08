<?php

namespace App\Services;

use App\Mail\ConfirmNewRecoveryEmail;
use App\Mail\RecoveryEmailChanged;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Cambio del email de recuperación: no cambia hasta que se confirma el nuevo con un código de 6 cifras
 * (1 h, 5 intentos). Al cambiar, se avisa al anterior por si no ha sido el dueño.
 */
class RecoveryEmailChange
{
    public const MINUTES = 60;

    public const MAX_ATTEMPTS = 5;

    public function start(User $user, string $email): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($user), [
            'email' => $email, 'hash' => hash('sha256', $code.$email), 'attempts' => 0,
        ], now()->addMinutes(self::MINUTES));

        Mail::to($email)->queue(new ConfirmNewRecoveryEmail($code));
    }

    /** El email pendiente de confirmar, si hay uno. */
    public function pending(User $user): ?string
    {
        return Cache::get($this->key($user))['email'] ?? null;
    }

    public function cancel(User $user): void
    {
        Cache::forget($this->key($user));
    }

    /** true si el código es correcto y el email ha cambiado. */
    public function confirm(User $user, string $code): bool
    {
        $entry = Cache::get($this->key($user));
        if (! $entry || $entry['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! hash_equals($entry['hash'], hash('sha256', preg_replace('/\D/', '', $code).$entry['email']))) {
            $entry['attempts']++;
            Cache::put($this->key($user), $entry, now()->addMinutes(self::MINUTES));

            return false;
        }

        $old = $user->email;
        $user->forceFill(['email' => $entry['email'], 'email_verified_at' => now()])->save();
        Mailbox::where('user_id', $user->id)->update(['can_send' => true]);   // ya hay un email verificado
        Cache::forget($this->key($user));
        app(EmailVerification::class)->forget($user);

        Mail::to($old)->queue(new RecoveryEmailChanged($entry['email']));

        return true;
    }

    private function key(User $user): string
    {
        return "recovery-email-change:{$user->id}";
    }
}
