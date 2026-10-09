<x-signup-layout title="Verificación en dos pasos">
    <div x-data="{ recovery: false }">
        <p class="mt-2 text-stone-600" x-show="! recovery">Escribe el código de 6 cifras de tu app de autenticación.</p>
        <p class="mt-2 text-stone-600" x-show="recovery" x-cloak>Escribe uno de tus códigos de recuperación (cada uno vale una sola vez).</p>
        <form method="POST" action="{{ route('login.two-factor.store') }}" class="mt-6">
            @csrf
            <label for="code" class="sr-only">Código</label>
            <input id="code" name="code" required autofocus autocomplete="one-time-code" maxlength="32"
                   :inputmode="recovery ? 'text' : 'numeric'" :placeholder="recovery ? 'xxxxx-xxxxx' : '123456'"
                   class="w-full rounded-2xl bg-stone-100 px-4 py-4 text-center font-mono text-2xl tracking-[0.3em] outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                   @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
            <x-form-error name="code" />
            <button class="mt-5 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white hover:bg-rojo-oscuro">Entrar</button>
        </form>
        <p class="mt-4 text-center text-sm">
            <button type="button" class="font-semibold text-stone-600 underline" @click="recovery = ! recovery; $nextTick(() => document.getElementById('code').focus())"
                    x-text="recovery ? 'Usar el código de la app' : 'He perdido el móvil: usar un código de recuperación'"></button>
        </p>
    </div>
</x-signup-layout>
