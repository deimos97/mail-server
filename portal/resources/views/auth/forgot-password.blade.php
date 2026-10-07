<x-signup-layout title="Recuperar la contraseña">
    <p class="mt-2 text-stone-600">Escribe tu dirección o tu email de recuperación. Te enviaremos un enlace a tu email de recuperación para elegir una contraseña nueva.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="login" class="block font-semibold">Tu dirección o tu email de recuperación</label>
            <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"
                   class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60">
            <x-form-error name="login" />
        </div>
        <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Enviarme el enlace</button>
    </form>
    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('login') }}" class="font-medium underline">Volver a entrar</a></p>
    </x-slot:after>
</x-signup-layout>
