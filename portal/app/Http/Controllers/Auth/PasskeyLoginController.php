<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelPasskeys\Http\Controllers\AuthenticateUsingPasskeyController;
use Spatie\LaravelPasskeys\Http\Requests\AuthenticateUsingPasskeysRequest;
use Throwable;

/**
 * Entrar con una passkey (sustituye a la contraseña y a la verificación en dos pasos: ya es un factor fuerte).
 * Como el login normal, no deja entrar a los administradores (su acceso es /admin, con su propia 2FA).
 */
class PasskeyLoginController extends AuthenticateUsingPasskeyController
{
    /** Una respuesta malformada o manipulada no debe dar un error 500: es una passkey no válida. */
    public function __invoke(AuthenticateUsingPasskeysRequest $request)
    {
        try {
            return parent::__invoke($request);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->invalidPasskeyResponse();
        }
    }

    protected function logInAuthenticatable(Authenticatable $authenticatable, bool $remember = false): self
    {
        if ($authenticatable->is_admin ?? false) {
            throw ValidationException::withMessages(['login' => 'No se ha podido entrar con esa passkey.']);
        }

        return parent::logInAuthenticatable($authenticatable, $remember);
    }
}
