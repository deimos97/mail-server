<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Services\OAuthServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Endpoints del proveedor OAuth2 del login único (D-010). Ver App\Services\OAuthServer.
 *
 *   GET|POST /oauth/authorize   (web, con sesión) → redirige a Roundcube con ?code&state
 *   POST     /oauth/token       (Roundcube, servidor a servidor)
 *   GET      /oauth/userinfo    (Roundcube, Bearer) → la dirección del buzón
 *   POST     /oauth/introspect  (Dovecot, solo desde el propio servidor)
 */
class OAuthController extends Controller
{
    public function __construct(private OAuthServer $oauth) {}

    public function authorize(Request $request): View|RedirectResponse
    {
        if ($error = $this->invalidClient($request)) {
            return $error;
        }
        if ($request->query('response_type') !== 'code') {
            return $this->back($request, ['error' => 'unsupported_response_type']);
        }

        $mailboxes = Mailbox::with('domain')->where('user_id', $request->user()->id)
            ->where('active', true)->where('status', 'active')->orderBy('id')->get();

        // "Abrir mi correo" desde /cuenta ya dice qué buzón; si solo hay uno, ese
        $chosen = $request->session()->pull('oauth.mailbox');
        $mailbox = $mailboxes->firstWhere('id', $chosen) ?? ($mailboxes->count() === 1 ? $mailboxes->first() : null);

        if ($mailbox) {
            return $this->issue($request, $mailbox);
        }
        if ($mailboxes->isEmpty()) {
            return $this->back($request, ['error' => 'access_denied', 'error_description' => 'No tienes ningún buzón activo']);
        }

        return view('oauth.choose', ['mailboxes' => $mailboxes, 'params' => $this->params($request)]);
    }

    public function choose(Request $request): View|RedirectResponse
    {
        if ($error = $this->invalidClient($request)) {
            return $error;
        }
        $mailbox = Mailbox::with('domain')->where('user_id', $request->user()->id)
            ->where('active', true)->where('status', 'active')->findOrFail($request->integer('mailbox'));

        return $this->issue($request, $mailbox);
    }

    public function token(Request $request): JsonResponse
    {
        [$clientId, $secret] = $this->clientCredentials($request);
        if (! $this->oauth->clientIsValid($clientId, $secret, checkSecret: true)) {
            return $this->tokenError('invalid_client', 401);
        }

        $result = match ($request->input('grant_type')) {
            'authorization_code' => $this->oauth->exchangeCode((string) $request->input('code'),
                $request->input('redirect_uri'), $request->input('code_verifier')),
            'refresh_token' => $this->oauth->refresh((string) $request->input('refresh_token')),
            default => 'unsupported_grant_type',
        };

        if (is_string($result)) {
            return $this->tokenError($result);
        }

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $result['expires_in'],
            'refresh_token' => $result['refresh_token'],
            'scope' => 'email',
        ])->header('Cache-Control', 'no-store')->header('Pragma', 'no-cache');
    }

    public function userinfo(Request $request): JsonResponse
    {
        $mailbox = $this->oauth->mailboxForAccessToken($request->bearerToken());
        if (! $mailbox) {
            return response()->json(['error' => 'invalid_token'], 401)
                ->header('WWW-Authenticate', 'Bearer error="invalid_token"');
        }

        return response()->json(['sub' => (string) $mailbox->id, 'email' => $mailbox->email, 'email_verified' => true])
            ->header('Cache-Control', 'no-store');
    }

    /** Para Dovecot (passdb oauth2, introspection_mode = post). Solo desde las IPs del servidor. */
    public function introspect(Request $request): JsonResponse
    {
        abort_unless(in_array($request->ip(), config('oauth.introspection_ips'), true), 403);

        $mailbox = $this->oauth->mailboxForAccessToken($request->input('token'));

        return response()->json($mailbox ? ['active' => true, 'username' => $mailbox->email] : ['active' => false])
            ->header('Cache-Control', 'no-store');
    }

    private function issue(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $code = $this->oauth->issueCode($request->user(), $mailbox, $request->input('redirect_uri'),
            $request->input('code_challenge'), $request->input('code_challenge_method'));

        return $this->back($request, ['code' => $code]);
    }

    /** Cliente o redirect_uri no válidos: error en pantalla, nunca redirigir a una URL no registrada. */
    private function invalidClient(Request $request): ?View
    {
        if ($this->oauth->clientIsValid($request->input('client_id')) && $this->oauth->redirectUriIsValid($request->input('redirect_uri'))) {
            return null;
        }

        return view('placeholder', ['title' => 'Enlace no válido', 'text' => 'Este enlace de acceso al correo no es válido. Vuelve a abrir tu correo desde tu cuenta.']);
    }

    private function back(Request $request, array $params): RedirectResponse
    {
        $params += array_filter(['state' => $request->input('state')]);
        $uri = $request->input('redirect_uri');

        return redirect()->away($uri.(str_contains($uri, '?') ? '&' : '?').http_build_query($params));
    }

    private function params(Request $request): array
    {
        return $request->only(['client_id', 'redirect_uri', 'response_type', 'state', 'scope', 'code_challenge', 'code_challenge_method']);
    }

    /** client_secret_basic (cabecera) o client_secret_post (cuerpo). */
    private function clientCredentials(Request $request): array
    {
        if ($request->getUser() !== null) {
            return [urldecode($request->getUser()), urldecode((string) $request->getPassword())];
        }

        return [$request->input('client_id'), $request->input('client_secret')];
    }

    private function tokenError(string $error, int $status = 400): JsonResponse
    {
        return response()->json(['error' => $error], $status)->header('Cache-Control', 'no-store');
    }
}
