<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Services\AccountDeletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** "Mi cuenta" → borrar la cuenta (D-009). Pide la contraseña y una confirmación explícita. */
class AccountDeletionController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.delete', [
            'mailboxes' => Mailbox::where('user_id', $request->user()->id)->where('status', '!=', 'deleted')->pluck('email'),
        ]);
    }

    public function destroy(Request $request, AccountDeletion $deletion): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Marca la casilla para confirmar que entiendes que no se puede deshacer.']);

        if (! Hash::check($data['password'], $request->user()->password)) {
            return back()->withErrors(['password' => 'La contraseña no es correcta.']);
        }

        // Primero cerrar la sesión: logout() guarda el usuario (remember_token) y, si ya estuviera borrado, lo volvería a crear
        $user = $request->user();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $deletion->delete($user);

        return redirect()->route('login')->with('status', 'Hemos borrado tu cuenta y tu correo. Gracias por habernos probado.');
    }
}
