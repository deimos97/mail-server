<?php

namespace App\Models;

use App\Mail\ResetPasswordEmail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\OnPortalDatabase;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use SensitiveParameter;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, OnPortalDatabase;

    protected $attributes = ['is_admin' => false];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'terms_accepted_at' => 'datetime',
            'attribution' => 'array',
        ];
    }

    /** Recuperar contraseña: nuestro correo en español, por la cola. */
    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        Mail::to($this->email)->queue(
            new ResetPasswordEmail(route('password.reset', ['token' => $token, 'email' => $this->email]))
        );
    }

    /** Buzones del usuario (BD `mailserver`). */
    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class);
    }

    /** D-014: un buzón gratis por usuario; los demás, con plan de pago. Los borrados no cuentan. */
    public function hasFreeMailbox(): bool
    {
        return $this->mailboxes()->where('status', '!=', 'deleted')
            ->whereIn('plan_id', Plan::where('is_free', true)->pluck('id'))
            ->exists();
    }

    /** Solo los administradores entran al panel /admin. is_admin no es asignable en masa. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
