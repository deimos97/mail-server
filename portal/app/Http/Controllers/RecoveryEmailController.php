<?php

namespace App\Http\Controllers;

use App\Services\RecoveryEmailChange;
use App\Support\RecoveryEmailRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** "Mi cuenta" → cambiar el email de recuperación (pide la contraseña y confirma el nuevo con un código). */
class RecoveryEmailController extends Controller
{
    public function __construct(private RecoveryEmailChange $change) {}

    public function edit(Request $request): View
    {
        return view('account.email', ['user' => $request->user(), 'pending' => $this->change->pending($request->user())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => RecoveryEmailRules::rules(),
            'password' => ['required', 'string'],
        ], RecoveryEmailRules::messages());

        if (! Hash::check($data['password'], $request->user()->password)) {
            return back()->withInput($request->only('email'))->withErrors(['password' => 'La contraseña no es correcta.']);
        }

        $this->change->start($request->user(), mb_strtolower($data['email']));

        return redirect()->route('account.email');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:7']]);

        if (! $this->change->confirm($request->user(), $request->input('code'))) {
            return back()->withErrors(['code' => $this->change->pending($request->user())
                ? 'El código no es correcto.'
                : 'El código ha caducado o has agotado los intentos. Vuelve a empezar.']);
        }

        return redirect()->route('account')->with('status', 'Listo: tu email de recuperación ahora es '.$request->user()->email.'.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->change->cancel($request->user());

        return redirect()->route('account');
    }
}
