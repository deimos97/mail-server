<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** Segundo paso del login si la cuenta tiene la verificación en dos pasos (código de la app o de recuperación). */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'login.two_factor';

    public function create(Request $request): View|RedirectResponse
    {
        return $this->pending($request) ? view('auth.two-factor') : redirect()->route('login');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:32']]);
        $pending = $this->pending($request) ?? abort(redirect()->route('login'));
        $user = User::find($pending['user']) ?? abort(redirect()->route('login'));

        $key = 'two-factor-login:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Demasiados intentos. Espera unos minutos.']);
        }
        if (! $twoFactor->verifyLogin($user, $request->input('code'))) {
            RateLimiter::hit($key, 15 * 60);

            return back()->withErrors(['code' => 'El código no es correcto.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget(self::SESSION_KEY);
        Auth::login($user, $pending['remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    /** @return array{user: int, remember: bool, until: int}|null */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        return $pending && $pending['until'] > now()->timestamp ? $pending : null;
    }
}
