<?php

namespace App\Http\Controllers;

use App\Models\AppPassword;
use App\Models\Mailbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Mi cuenta" mínimo (Fase 2): sus buzones, abrir el webmail y sus dispositivos. El resto, en la Fase 3. */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', $this->data($request));
    }

    /**
     * Revoca la contraseña de un dispositivo. Dovecot deja de aceptarla al momento (no cachea); una
     * conexión ya abierta (IMAP IDLE) puede seguir viva hasta que el dispositivo reconecte.
     */
    public function revokeDevice(Request $request, int $device): RedirectResponse
    {
        $appPassword = AppPassword::whereNull('revoked_at')
            ->whereIn('mailbox_id', Mailbox::where('user_id', $request->user()->id)->pluck('id'))
            ->findOrFail($device);
        $appPassword->update(['revoked_at' => now()]);

        return redirect()->route('account')->with('status', "Hemos desconectado «{$appPassword->name}». Ya no puede entrar en tu correo.");
    }

    /** "Abrir mi correo": recuerda qué buzón y manda a Roundcube, que arranca el login único. */
    public function openWebmail(Request $request, int $mailbox): RedirectResponse
    {
        $mailbox = Mailbox::where('user_id', $request->user()->id)->findOrFail($mailbox);
        $request->session()->put('oauth.mailbox', $mailbox->id);

        return redirect()->away(config('oauth.webmail_start_url'));
    }

    private function data(Request $request): array
    {
        return [
            'user' => $request->user(),
            'mailboxes' => Mailbox::where('user_id', $request->user()->id)
                ->with(['plan', 'appPasswords' => fn ($q) => $q->whereNull('revoked_at')->latest('id')])
                ->orderBy('id')->get(),
        ];
    }
}
