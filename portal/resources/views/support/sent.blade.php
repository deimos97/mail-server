<x-signup-layout title="Mensaje enviado">
    <p class="mt-2 text-stone-600">
        Lo hemos registrado con el número <strong class="text-tinta">{{ $ticket->number() }}</strong>.
        Te hemos enviado una confirmación a <strong class="text-tinta" data-ph-mask>{{ $ticket->email }}</strong> y te contestaremos ahí.
    </p>
    <p class="mt-3 text-sm text-stone-500">Si no la ves en unos minutos, mira en la carpeta de spam. Para añadir algo, responde a ese correo sin cambiar el asunto.</p>

    <div class="mt-6 flex flex-wrap gap-3">
        @auth
            <a href="{{ route('account') }}" class="rounded-xl bg-rojo px-4 py-2 font-semibold text-white hover:bg-rojo-oscuro">Volver a mi cuenta</a>
        @else
            <a href="{{ route('home') }}" class="rounded-xl bg-rojo px-4 py-2 font-semibold text-white hover:bg-rojo-oscuro">Volver al inicio</a>
        @endauth
    </div>
</x-signup-layout>
