<?php

namespace App\Http\Controllers;

use App\Jobs\KickMailboxConnections;
use App\Models\AppPassword;
use App\Models\Mailbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Mi cuenta": sus buzones (plan, uso, estado), abrir el webmail y sus dispositivos. */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', $this->data($request));
    }

    /**
     * Revoca la contraseña de un dispositivo. Dovecot deja de aceptarla al momento (no cachea) y se
     * cierran las conexiones abiertas del buzón (`doveadm kick`, tras enviar la respuesta): las demás
     * apps reconectan solas con su contraseña; la revocada ya no puede. No va por la cola: el worker
     * corre con NoNewPrivileges y no puede usar sudo.
     */
    public function revokeDevice(Request $request, int $device): RedirectResponse
    {
        $appPassword = $this->activeDevice($request, $device);
        $appPassword->update(['revoked_at' => now()]);
        KickMailboxConnections::dispatchAfterResponse($appPassword->mailbox->email);

        return redirect()->route('account')->with('status', "Hemos desconectado «{$appPassword->name}». Ya no puede entrar en tu correo.");
    }

    public function renameDevice(Request $request, int $device): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:64']]);
        $appPassword = $this->activeDevice($request, $device);
        $appPassword->update(['name' => trim($data['name'])]);

        return redirect()->route('account')->with('status', 'Nombre cambiado.');
    }

    /** "Abrir mi correo": recuerda qué buzón y manda a Roundcube, que arranca el login único. */
    public function openWebmail(Request $request, int $mailbox): RedirectResponse
    {
        $mailbox = Mailbox::where('user_id', $request->user()->id)->findOrFail($mailbox);
        $request->session()->put('oauth.mailbox', $mailbox->id);

        return redirect()->away(config('oauth.webmail_start_url'));
    }

    /** Un dispositivo no revocado de uno de sus buzones (si no, 404). */
    private function activeDevice(Request $request, int $id): AppPassword
    {
        return AppPassword::whereNull('revoked_at')
            ->whereIn('mailbox_id', Mailbox::where('user_id', $request->user()->id)->pluck('id'))
            ->findOrFail($id);
    }

    private function data(Request $request): array
    {
        return [
            'user' => $request->user(),
            'mailboxes' => Mailbox::where('user_id', $request->user()->id)
                ->with(['plan', 'domain', 'usage', 'lastLogins', 'appPasswords' => fn ($q) => $q->whereNull('revoked_at')->latest('id')])
                ->orderBy('id')->get(),
        ];
    }
}
