<x-signup-layout :step="4" title="Confirma tu email">
    <p class="mt-2 text-stone-600">
        Tu correo <strong data-ph-mask>{{ $mailbox?->email }}</strong> ya está creado y recibe mensajes.
        Para poder <strong>enviar</strong>, confirma tu email de recuperación.
    </p>
    <p class="mt-3 text-stone-600">Te hemos enviado un código a <strong data-ph-mask>{{ $user->email }}</strong>. Escríbelo aquí o pulsa el botón del correo.</p>

    <form method="POST" action="{{ route('signup.verify.store') }}" class="mt-6">
        @csrf
        <label for="code" class="block font-semibold">Código de 6 cifras</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus
               class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-4 text-center font-mono text-3xl tracking-[0.5em] outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
               @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
        <x-form-error name="code" />
        <button type="submit" class="mt-5 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Confirmar</button>
    </form>

    <div class="mt-6 border-t border-stone-200 pt-5 text-sm text-stone-600">
        <p>¿No te llega? Mira en la carpeta de spam o de promociones.</p>
        <form method="POST" action="{{ route('signup.verify.resend') }}" class="mt-2">@csrf
            <button class="font-semibold text-rojo underline">Enviarme otro código</button>
        </form>
    </div>

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500">
            Puedes confirmarlo más tarde: el correo ya funciona para recibir.
            <a href="{{ route('signup.done') }}" class="font-medium underline">Continuar sin confirmar</a>
        </p>
    </x-slot:after>
</x-signup-layout>
