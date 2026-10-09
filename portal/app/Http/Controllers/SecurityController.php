<?php

namespace App\Http\Controllers;

use App\Mail\SecurityNotice;
use App\Services\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Throwable;

/**
 * Mi cuenta → Seguridad: verificación en dos pasos (app de autenticación) y passkeys.
 * Activar, desactivar o regenerar códigos pide la contraseña; cada cambio se avisa por email.
 */
class SecurityController extends Controller
{
    public function __construct(private TwoFactor $twoFactor) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $pending = $request->session()->get('two_factor.pending');

        return view('account.security', [
            'user' => $user,
            'pending' => $pending,
            'qr' => $pending ? $this->twoFactor->qr($user, $pending) : null,
            'recoveryCodes' => $request->session()->get('two_factor.codes'),
            'passkeys' => $user->passkeys()->latest()->get(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->checkPassword($request);
        $request->session()->put('two_factor.pending', $this->twoFactor->newSecret());

        return redirect()->route('account.security');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $secret = $request->session()->get('two_factor.pending') ?? abort(404);

        if (! $this->twoFactor->verify($secret, $request->input('code'), $request->user()->id)) {
            return back()->withErrors(['code' => 'El código no es correcto. Comprueba la hora del móvil y prueba con el siguiente.']);
        }

        $codes = $this->twoFactor->enable($request->user(), $secret);
        $request->session()->forget('two_factor.pending');
        Mail::to($request->user()->email)->queue(new SecurityNotice('two_factor_on'));

        return redirect()->route('account.security')->with('two_factor.codes', $codes)
            ->with('status', 'Verificación en dos pasos activada.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('two_factor.pending');

        return redirect()->route('account.security');
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->checkPassword($request);
        $this->twoFactor->disable($request->user());
        Mail::to($request->user()->email)->queue(new SecurityNotice('two_factor_off'));

        return redirect()->route('account.security')->with('status', 'Verificación en dos pasos desactivada.');
    }

    public function regenerateCodes(Request $request): RedirectResponse
    {
        $this->checkPassword($request);
        abort_unless($request->user()->hasTwoFactor(), 404);
        $codes = $this->twoFactor->newRecoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return redirect()->route('account.security')->with('two_factor.codes', $codes)
            ->with('status', 'Nuevos códigos de recuperación. Los anteriores ya no valen.');
    }

    /** Opciones para crear una passkey (las firma el navegador). */
    public function passkeyOptions(Request $request, GeneratePasskeyRegisterOptionsAction $action): JsonResponse
    {
        $options = $action->execute($request->user());
        $request->session()->put('passkey-registration-options', $options);

        return response()->json(json_decode($options));
    }

    public function storePasskey(Request $request, StorePasskeyAction $action): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:64'], 'passkey' => ['required', 'json']]);
        $options = $request->session()->pull('passkey-registration-options');
        abort_unless($options, 422);

        try {
            $action->execute($request->user(), $data['passkey'], $options, $request->getHost(), ['name' => $data['name']]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No hemos podido guardar la passkey. Inténtalo de nuevo.'], 422);
        }
        Mail::to($request->user()->email)->queue(new SecurityNotice('passkey_added', $data['name']));
        $request->session()->flash('status', "Passkey «{$data['name']}» añadida.");

        return response()->json(['ok' => true]);
    }

    public function destroyPasskey(Request $request, int $passkey): RedirectResponse
    {
        $request->user()->passkeys()->whereKey($passkey)->firstOrFail()->delete();

        return redirect()->route('account.security')->with('status', 'Passkey eliminada.');
    }

    private function checkPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            back()->withErrors(['password' => 'La contraseña no es correcta.'])->throwResponse();
        }
    }
}
