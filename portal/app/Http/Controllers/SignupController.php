<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CaptureAttribution;
use App\Models\Domain;
use App\Models\MailboxReservation;
use App\Models\NamePriceTier;
use App\Models\Plan;
use App\Models\User;
use App\Services\EmailVerification;
use App\Services\NameAvailability;
use App\Services\ServerAnalytics;
use App\Services\Signup;
use App\Services\Turnstile;
use App\Support\RecoveryEmailRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;

/**
 * El alta (Fase 2): nombre → cuenta → plan → verificar email → ¡listo!
 *
 * En sesión: `signup.token` (aleatorio, identifica este alta; sobrevive al cambio de id de sesión
 * del login) y `signup.plan` (slug elegido en la landing). El nombre va en una MailboxReservation
 * ligada a ese token (columna session_id).
 */
class SignupController extends Controller
{
    public function __construct(private Signup $signup) {}

    /** Entrada: /alta?nombre=&dominio=&plan= (desde la landing) o elegir nombre aquí. */
    public function start(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->resume($request)) {
            return $redirect;
        }

        if ($plan = $request->query('plan')) {
            $request->session()->put('signup.plan', (string) $plan);
        }

        if ($name = $request->query('nombre')) {
            $domain = $this->domain($request->query('dominio'));
            if ($domain && $this->signup->reserve($name, $domain, $this->token($request), $request->user())) {
                return redirect()->route('signup.account');
            }
        }

        return view('signup.name', ['domains' => Domain::signup()->pluck('name')->all(), 'value' => $name]);
    }

    public function storeName(Request $request): RedirectResponse
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:128'], 'dominio' => ['nullable', 'string']]);
        $domain = $this->domain($data['dominio'] ?? null) ?? abort(422);

        if (! $this->signup->reserve($data['nombre'], $domain, $this->token($request), $request->user())) {
            $check = app(NameAvailability::class)->check($data['nombre'], $domain);

            return back()->withInput()->withErrors(['nombre' => $check->message ?? 'Ese nombre no está disponible.']);
        }

        return redirect()->route($request->user() ? 'signup.plan' : 'signup.account');
    }

    /** Paso 1: email de recuperación + contraseña. */
    public function account(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('signup.plan');
        }
        $reservation = $this->signup->currentReservation($this->token($request));
        if (! $reservation) {
            return redirect()->route('signup')->with('status', 'Tu reserva ha caducado. Vuelve a elegir tu nombre.');
        }

        return view('signup.account', ['reservation' => $reservation]);
    }

    public function storeAccount(Request $request, Turnstile $turnstile): RedirectResponse
    {
        $reservation = $this->signup->currentReservation($this->token($request));
        if (! $reservation) {
            return redirect()->route('signup')->with('status', 'Tu reserva ha caducado. Vuelve a elegir tu nombre.');
        }

        $data = $request->validate([
            'email' => RecoveryEmailRules::rules(),
            'password' => ['required', 'string', Password::min(10)->uncompromised()],
            'terms' => ['accepted'],
        ], [
            ...RecoveryEmailRules::messages(),
            'password.min' => 'La contraseña necesita al menos 10 caracteres.',
            'password.uncompromised' => 'Esa contraseña ha aparecido en filtraciones de datos. Elige otra.',
            'terms.accepted' => 'Tienes que aceptar las condiciones para continuar.',
        ]);

        if (! $turnstile->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput($request->except('password'))
                ->withErrors(['turnstile' => 'No hemos podido comprobar que no eres un robot. Inténtalo de nuevo.']);
        }

        $user = $this->signup->createUser($data['email'], $data['password'], $request->ip(),
            $request->session()->get(CaptureAttribution::SESSION_KEY));

        Auth::login($user);
        $request->session()->regenerate();   // el token del alta va en los datos de sesión: sobrevive
        MailboxReservation::where('session_id', $this->token($request))->update(['user_id' => $user->id]);

        app(EmailVerification::class)->send($user);

        return redirect()->route('signup.plan');
    }

    /** Paso 2: plan. En la Fase 2 solo el gratis; el buzón se crea aquí. */
    public function plan(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->resume($request, except: 'plan')) {
            return $redirect;
        }
        $reservation = $this->signup->currentReservation($this->token($request));
        if (! $reservation) {
            return redirect()->route('signup')->with('status', 'Tu reserva ha caducado. Vuelve a elegir tu nombre.');
        }

        return view('signup.plan', [
            'reservation' => $reservation,
            'plans' => Plan::visible()->ordered()->with('offers')->get(),
            'surcharge' => NamePriceTier::forLength(mb_strlen($reservation->local_part)),
            'selected' => $request->session()->get('signup.plan'),
        ]);
    }

    public function storePlan(Request $request, ServerAnalytics $analytics): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', 'string', Rule::exists('plans', 'slug')]]);
        $reservation = $this->signup->currentReservation($this->token($request));
        if (! $reservation) {
            return redirect()->route('signup')->with('status', 'Tu reserva ha caducado. Vuelve a elegir tu nombre.');
        }

        $plan = Plan::where('slug', $data['plan'])->firstOrFail();
        try {
            $mailbox = $this->signup->provision($request->user(), $reservation, $plan, $this->token($request));
        } catch (RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        $analytics->signupCompleted($request, $mailbox, $plan);

        $request->session()->forget('signup.plan');

        return redirect()->route($request->user()->hasVerifiedEmail() ? 'signup.done' : 'signup.verify');
    }

    /** Paso 3: verificar el email de recuperación (código o enlace). */
    public function verify(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('signup.done');
        }

        return view('signup.verify', ['user' => $request->user(), 'mailbox' => $request->user()->mailboxes()->first()]);
    }

    public function storeVerify(Request $request, EmailVerification $verification): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:12']]);
        $key = 'verify-code:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['code' => 'Demasiados intentos. Espera unos minutos.']);
        }
        RateLimiter::hit($key, 600);

        if (! $verification->verifyCode($request->user(), $request->input('code'))) {
            return back()->withErrors(['code' => $verification->codeExhausted($request->user())
                ? 'El código ha caducado o se ha usado demasiadas veces. Pide uno nuevo.'
                : 'El código no es correcto.']);
        }

        return redirect()->route('signup.done');
    }

    public function verifyLink(Request $request, EmailVerification $verification, int $user, string $hash): View|RedirectResponse
    {
        $account = User::findOrFail($user);
        abort_unless(hash_equals(sha1($account->email), $hash), 403);

        $verification->markVerified($account);

        // Si lo abre en el mismo navegador del alta, sigue el flujo; si no (otro dispositivo), confirmación
        return $request->user()?->is($account)
            ? redirect()->route('signup.done')
            : view('signup.verified');
    }

    public function resend(Request $request, EmailVerification $verification): RedirectResponse
    {
        $key = 'verify-resend:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['code' => 'Ya te hemos enviado varios correos. Espera unos minutos y revisa la carpeta de spam.']);
        }
        RateLimiter::hit($key, 600);
        $verification->send($request->user());

        return back()->with('status', 'Te hemos enviado un código nuevo.');
    }

    /** ¡Listo! (la pantalla completa, con el webmail y el móvil, llega en el bloque E). */
    public function done(Request $request): View|RedirectResponse
    {
        $mailbox = $request->user()->mailboxes()->first();
        if (! $mailbox) {
            return redirect()->route('signup.plan');
        }

        return view('signup.done', ['mailbox' => $mailbox, 'verified' => $request->user()->hasVerifiedEmail()]);
    }

    /** Elegir otro nombre: libera la reserva. */
    public function changeName(Request $request): RedirectResponse
    {
        MailboxReservation::where('session_id', $this->token($request))->delete();

        return redirect()->route('signup');
    }

    /** Si ya tiene cuenta y buzón, el alta continúa donde la dejó. */
    private function resume(Request $request, ?string $except = null): ?RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }
        if ($user->mailboxes()->exists()) {
            return redirect()->route($user->hasVerifiedEmail() ? 'signup.done' : 'signup.verify');
        }
        if ($except !== 'plan' && $this->signup->currentReservation($this->token($request))) {
            return redirect()->route('signup.plan');
        }

        return null;
    }

    /** Token de este alta, guardado en la sesión. Identifica su reserva de nombre. */
    private function token(Request $request): string
    {
        if (! $request->session()->has('signup.token')) {
            $request->session()->put('signup.token', Str::random(40));
        }

        return $request->session()->get('signup.token');
    }

    private function domain(?string $name): ?Domain
    {
        return Domain::signup()->when($name, fn ($q) => $q->where('name', mb_strtolower($name)))->first();
    }
}
