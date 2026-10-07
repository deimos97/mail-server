<x-signup-layout title="Entrar">
    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5" x-data="{ show: false }">
        @csrf
        <div>
            <label for="login" class="block font-semibold">Tu dirección o tu email de recuperación</label>
            <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username"
                   autocapitalize="none" spellcheck="false" placeholder="tunombre@unagrandeylibre.es"
                   class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent placeholder:text-stone-400 focus:bg-white focus:ring-rojo/60"
                   @error('login') aria-invalid="true" aria-describedby="login-error" @enderror>
            <x-form-error name="login" />
        </div>
        <div>
            <div class="flex items-baseline justify-between">
                <label for="password" class="block font-semibold">Contraseña</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-rojo underline">¿La has olvidado?</a>
            </div>
            <div class="relative mt-2">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password"
                       class="w-full rounded-2xl bg-stone-100 py-3.5 pl-4 pr-24 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 my-auto h-9 rounded-xl px-3 text-sm font-semibold text-stone-600 hover:bg-stone-200" x-text="show ? 'Ocultar' : 'Mostrar'">Mostrar</button>
            </div>
        </div>
        <label class="flex items-center gap-3 text-sm text-stone-600">
            <input type="checkbox" name="remember" value="1" class="size-5 accent-rojo"> Mantener la sesión en este dispositivo
        </label>
        <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Entrar</button>
    </form>

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500">¿Aún no tienes cuenta? <a href="{{ route('home') }}" class="font-medium underline">Consigue tu correo</a></p>
    </x-slot:after>
</x-signup-layout>
