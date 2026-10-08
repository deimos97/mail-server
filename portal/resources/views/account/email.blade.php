<x-signup-layout title="Email de recuperación">
    <p class="mt-2 text-stone-600">
        Es el email al que te enviamos los avisos y el enlace para recuperar tu contraseña. También sirve para entrar en la web.
        Ahora es <strong data-ph-mask>{{ $user->email }}</strong>.
    </p>

    @if ($pending)
        <p class="mt-5 text-stone-600">Te hemos enviado un código a <strong data-ph-mask>{{ $pending }}</strong>. Escríbelo aquí para terminar el cambio.</p>
        <form method="POST" action="{{ route('account.email.confirm') }}" class="mt-4">
            @csrf
            <label for="code" class="block font-semibold">Código de 6 cifras</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus
                   class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-4 text-center font-mono text-3xl tracking-[0.5em] outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                   @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
            <x-form-error name="code" />
            <button type="submit" class="mt-5 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Confirmar el cambio</button>
        </form>
        <form method="POST" action="{{ route('account.email.cancel') }}" class="mt-4 text-center text-sm">
            @csrf
            <button class="font-semibold text-stone-600 underline">Cancelar o usar otro email</button>
        </form>
    @else
        <form method="POST" action="{{ route('account.email.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="block font-semibold">Nuevo email de recuperación</label>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus value="{{ old('email') }}" data-ph-mask
                       class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                <x-form-error name="email" />
            </div>
            <div>
                <label for="password" class="block font-semibold">Tu contraseña de la web</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <x-form-error name="password" />
            </div>
            <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Enviarme un código</button>
        </form>
    @endif

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
