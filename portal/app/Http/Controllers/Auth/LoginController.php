<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Login de clientes: con el email de recuperación o con cualquiera de sus direcciones (D-011).
 * Los administradores no entran por aquí: su login (/admin) es el que exige la 2FA.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:320'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identifier = mb_strtolower(trim($data['login']));
        $key = 'login:'.Str::transliterate($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->onlyInput('login')->withErrors(['login' => "Demasiados intentos. Prueba otra vez en {$minutes} min."]);
        }

        $user = $this->findUser($identifier);

        // Mismo mensaje y mismo coste (hash) exista o no la cuenta: no revela quién tiene cuenta
        $valid = Hash::check($data['password'], $user?->password ?? '$2y$12$'.str_repeat('a', 53));
        if (! $user || ! $valid || $user->is_admin) {
            RateLimiter::hit($key, 15 * 60);

            return back()->onlyInput('login')->withErrors(['login' => 'El email o la contraseña no son correctos.']);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Por email de recuperación o por una de sus direcciones de correo. */
    public static function findUser(string $identifier): ?User
    {
        $user = User::where('email', $identifier)->first();
        if ($user) {
            return $user;
        }

        $userId = Mailbox::where('email', $identifier)->whereNotNull('user_id')->value('user_id');

        return $userId ? User::find($userId) : null;
    }
}
