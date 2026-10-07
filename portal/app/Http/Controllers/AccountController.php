<?php

namespace App\Http\Controllers;

use App\Models\AppPassword;
use App\Models\Mailbox;
use App\Services\AppPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/** "Mi cuenta" mínimo (Fase 2): sus buzones y conectar dispositivos. El resto, en la Fase 3. */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', $this->data($request));
    }

    /**
     * Genera la contraseña de un dispositivo y la muestra en esta misma respuesta: no pasa por la
     * sesión ni se guarda en claro en ningún sitio.
     */
    public function storeDevice(Request $request, AppPasswords $appPasswords): View
    {
        $data = $request->validate([
            'mailbox' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:64'],
        ], ['name.required' => 'Ponle un nombre para reconocerlo (por ejemplo, "iPhone de Ana").']);

        $mailbox = Mailbox::where('user_id', $request->user()->id)->findOrFail($data['mailbox']);

        try {
            [$device, $password] = $appPasswords->create($mailbox, $data['name']);
        } catch (RuntimeException $e) {
            return view('account.show', $this->data($request) + ['deviceError' => $e->getMessage()]);
        }

        return view('account.show', $this->data($request) + [
            'newDevice' => ['mailbox' => $mailbox, 'device' => $device, 'password' => $password],
        ]);
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
