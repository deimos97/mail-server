<?php

namespace App\Services;

use App\Models\Mailbox;
use App\Models\OauthCode;
use App\Models\OauthToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Proveedor OAuth2 mínimo para el login único con el webmail (D-010).
 *
 * - Flujo "authorization code" para un único cliente confidencial (Roundcube), PKCE opcional.
 * - El token pertenece a un BUZÓN: Roundcube entra en IMAP/SMTP como ese buzón y Dovecot lo
 *   confirma preguntando a introspect().
 * - Códigos y tokens: aleatorios, solo se guarda su SHA-256. Códigos de un uso (60 s); si se
 *   reutiliza uno, se revocan los tokens que generó (RFC 6749 §4.1.2). Refresh rotativo.
 * - Un token solo vale mientras su buzón esté activo: suspender un buzón corta el webmail al momento.
 */
class OAuthServer
{
    public function clientIsValid(?string $clientId, ?string $secret = null, bool $checkSecret = false): bool
    {
        $client = config('oauth.client');
        if (! $client['secret'] || ! is_string($clientId) || ! hash_equals($client['id'], $clientId)) {
            return false;
        }

        return ! $checkSecret || (is_string($secret) && hash_equals($client['secret'], $secret));
    }

    public function redirectUriIsValid(?string $uri): bool
    {
        return is_string($uri) && hash_equals(config('oauth.client.redirect_uri'), $uri);
    }

    public function issueCode(User $user, Mailbox $mailbox, string $redirectUri, ?string $challenge, ?string $method): string
    {
        $code = $this->random();
        OauthCode::create([
            'code_hash' => $this->hash($code),
            'user_id' => $user->id,
            'mailbox_id' => $mailbox->id,
            'redirect_uri' => $redirectUri,
            'code_challenge' => $challenge,
            'code_challenge_method' => $challenge ? ($method ?: 'plain') : null,
            'expires_at' => now()->addSeconds(config('oauth.code_ttl')),
        ]);

        return $code;
    }

    /**
     * Canjea un código por tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, mailbox: Mailbox}|string error OAuth
     */
    public function exchangeCode(string $code, ?string $redirectUri, ?string $verifier): array|string
    {
        $row = OauthCode::where('code_hash', $this->hash($code))->first();
        if (! $row) {
            return 'invalid_grant';
        }

        // De un solo uso, de forma atómica: si ya se usó, se revoca lo que generó
        $claimed = OauthCode::whereKey($row->id)->whereNull('used_at')->update(['used_at' => now()]);
        if (! $claimed) {
            OauthToken::where('code_id', $row->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

            return 'invalid_grant';
        }

        if ($row->expires_at->isPast() || ! $this->redirectUriIsValid($redirectUri) || ! hash_equals($row->redirect_uri, (string) $redirectUri)) {
            return 'invalid_grant';
        }
        if ($row->code_challenge && ! $this->pkceMatches($row->code_challenge, $row->code_challenge_method, $verifier)) {
            return 'invalid_grant';
        }

        $mailbox = $this->activeMailbox($row->mailbox_id, $row->user_id);
        if (! $mailbox) {
            return 'invalid_grant';
        }

        return $this->issueTokens($row->user_id, $mailbox, $row->id);
    }

    /** @return array{access_token: string, refresh_token: string, expires_in: int, mailbox: Mailbox}|string */
    public function refresh(string $refreshToken): array|string
    {
        return DB::transaction(function () use ($refreshToken) {
            $row = OauthToken::where('refresh_hash', $this->hash($refreshToken))->lockForUpdate()->first();
            if (! $row || $row->revoked_at || $row->refresh_expires_at?->isPast()) {
                return 'invalid_grant';
            }

            $mailbox = $this->activeMailbox($row->mailbox_id, $row->user_id);
            if (! $mailbox) {
                return 'invalid_grant';
            }

            $row->update(['revoked_at' => now()]);   // rotación: el refresh viejo deja de valer

            return $this->issueTokens($row->user_id, $mailbox, $row->code_id);
        });
    }

    /** El buzón de un access token vigente (y con el buzón activo), o null. */
    public function mailboxForAccessToken(?string $accessToken): ?Mailbox
    {
        if (! $accessToken) {
            return null;
        }
        $row = OauthToken::where('access_hash', $this->hash($accessToken))
            ->whereNull('revoked_at')->where('access_expires_at', '>', now())->first();

        return $row ? $this->activeMailbox($row->mailbox_id, $row->user_id) : null;
    }

    public function revokeForUser(User $user): void
    {
        OauthToken::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    private function issueTokens(int $userId, Mailbox $mailbox, ?int $codeId): array
    {
        $access = $this->random();
        $refresh = $this->random();

        OauthToken::create([
            'access_hash' => $this->hash($access),
            'refresh_hash' => $this->hash($refresh),
            'user_id' => $userId,
            'mailbox_id' => $mailbox->id,
            'code_id' => $codeId,
            'access_expires_at' => now()->addSeconds(config('oauth.access_ttl')),
            'refresh_expires_at' => now()->addSeconds(config('oauth.refresh_ttl')),
        ]);

        return ['access_token' => $access, 'refresh_token' => $refresh, 'expires_in' => config('oauth.access_ttl'), 'mailbox' => $mailbox];
    }

    private function activeMailbox(int $mailboxId, int $userId): ?Mailbox
    {
        return Mailbox::with('domain')->whereKey($mailboxId)->where('user_id', $userId)
            ->where('active', true)->where('status', 'active')->first();
    }

    private function pkceMatches(string $challenge, ?string $method, ?string $verifier): bool
    {
        if (! $verifier) {
            return false;
        }
        $computed = $method === 'S256' ? rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=') : $verifier;

        return hash_equals($challenge, $computed);
    }

    private function random(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
