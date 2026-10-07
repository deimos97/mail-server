<x-signup-layout :step="2" title="Crea tu cuenta">
    {{-- El formulario de "Cambiar" va fuera del párrafo: un <form> dentro de un <p> lo parte en dos --}}
    <div class="mt-2 text-stone-600">
        <span>Tu dirección <strong data-ph-mask>{{ $reservation->email() }}</strong> está reservada durante unos minutos.</span>
        <form method="POST" action="{{ route('signup.change-name') }}" class="inline">@csrf<button class="font-medium text-rojo underline">Cambiar</button></form>
    </div>

    <form method="POST" action="{{ route('signup.account.store') }}" class="mt-6 space-y-5" x-data="{ show: false }">
        @csrf
        <div>
            <label for="email" class="block font-semibold">Email de recuperación</label>
            <p class="text-sm text-stone-500">Otro email que ya uses (Gmail, Outlook…). Lo usaremos para confirmar tu cuenta y si olvidas la contraseña.</p>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email"
                   class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <x-form-error name="email" />
        </div>

        <div>
            <label for="password" class="block font-semibold">Contraseña</label>
            <p class="text-sm text-stone-500">Mínimo 10 caracteres. Es la única que tendrás que recordar.</p>
            <div class="relative mt-2">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="new-password" minlength="10"
                       class="w-full rounded-2xl bg-stone-100 py-3.5 pl-4 pr-24 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 my-auto h-9 rounded-xl px-3 text-sm font-semibold text-stone-600 hover:bg-stone-200"
                        x-text="show ? 'Ocultar' : 'Mostrar'">Mostrar</button>
            </div>
            <x-form-error name="password" />
        </div>

        <div>
            <label class="flex items-start gap-3">
                <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-1 size-5 accent-rojo">
                <span class="text-sm text-stone-600">
                    Acepto las <a href="{{ route('legal', 'condiciones') }}" target="_blank" class="font-medium text-tinta underline">condiciones del servicio</a>,
                    la <a href="{{ route('legal', 'uso-aceptable') }}" target="_blank" class="font-medium text-tinta underline">política de uso aceptable</a>
                    y la <a href="{{ route('legal', 'privacidad') }}" target="_blank" class="font-medium text-tinta underline">política de privacidad</a>.
                </span>
            </label>
            <x-form-error name="terms" />
        </div>

        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="es" data-theme="light" data-size="flexible"></div>
        <x-form-error name="turnstile" />

        <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">
            Crear cuenta
        </button>
    </form>

    {{-- Turnstile solo en esta página (antibots del alta; sin cookies de seguimiento) --}}
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</x-signup-layout>
