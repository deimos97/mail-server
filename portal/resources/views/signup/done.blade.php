{{-- PROVISIONAL (bloque B): en el bloque E esta pantalla tendrá "Abrir mi correo" (login único) y "Configura tu móvil". --}}
<x-signup-layout title="¡Ya tienes tu correo!">
    <p class="mt-4 rounded-2xl bg-amarillo/20 px-4 py-3 text-center text-xl font-bold" data-ph-mask>{{ $mailbox->email }}</p>

    @unless ($verified)
        <p class="mt-4 text-stone-600">
            Ya recibe correo. Para poder enviar, confirma tu email de recuperación.
            <a href="{{ route('signup.verify') }}" class="font-semibold text-rojo underline">Confirmar ahora</a>
        </p>
    @else
        <p class="mt-4 text-stone-600">Ya puedes recibir y enviar correo.</p>
    @endunless

    <p class="mt-6 text-sm text-stone-500">Muy pronto podrás abrir tu correo desde aquí y configurarlo en el móvil en un par de toques.</p>
</x-signup-layout>
