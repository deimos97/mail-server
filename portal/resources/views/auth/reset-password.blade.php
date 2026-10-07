<x-signup-layout title="Elige una contraseña nueva">
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5" x-data="{ show: false }">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <div>
            <label for="password" class="block font-semibold">Contraseña nueva</label>
            <p class="text-sm text-stone-500">Mínimo 10 caracteres. Se cerrará la sesión en tus otros dispositivos.</p>
            <div class="relative mt-2">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autofocus autocomplete="new-password" minlength="10"
                       class="w-full rounded-2xl bg-stone-100 py-3.5 pl-4 pr-24 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 my-auto h-9 rounded-xl px-3 text-sm font-semibold text-stone-600 hover:bg-stone-200" x-text="show ? 'Ocultar' : 'Mostrar'">Mostrar</button>
            </div>
            <x-form-error name="password" />
        </div>
        <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Guardar contraseña</button>
    </form>
</x-signup-layout>
