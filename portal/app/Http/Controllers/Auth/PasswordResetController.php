<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Recuperar la contraseña de la web. El enlace va SIEMPRE al email de recuperación, aunque se
 * pida con una dirección @unagrandeylibre.es (si ha olvidado la contraseña, no puede leer ese buzón).
 */
class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['login' => ['required', 'string', 'max:320']]);

        $user = LoginController::findUser(mb_strtolower(trim($request->input('login'))));
        if ($user && ! $user->is_admin) {
            Password::sendResetLink(['email' => $user->email]);
        }

        // Siempre la misma respuesta: no revela si la cuenta existe
        return back()->with('status', 'Si hay una cuenta con ese dato, te hemos enviado un enlace a tu email de recuperación. Mira también en spam.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', PasswordRule::min(10)->uncompromised()],
        ], [
            'password.min' => 'La contraseña necesita al menos 10 caracteres.',
            'password.uncompromised' => 'Esa contraseña ha aparecido en filtraciones de datos. Elige otra.',
        ]);

        $status = Password::reset($request->only('email', 'password', 'token'), function ($user, string $password) {
            $user->password = $password;
            $user->setRememberToken(null);
            $user->save();
            // Cierra las sesiones abiertas en otros dispositivos
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        if ($status !== Password::PasswordReset) {
            return back()->withErrors(['password' => 'El enlace ha caducado o ya se ha usado. Pide uno nuevo.']);
        }

        return redirect()->route('login')->with('status', 'Contraseña cambiada. Ya puedes entrar.');
    }
}
