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

    <a href="{{ route('account.webmail', $mailbox->id) }}" class="mt-6 block w-full rounded-2xl bg-rojo px-6 py-4 text-center text-lg font-bold text-white transition hover:bg-rojo-oscuro">Abrir mi correo</a>
    <a href="{{ route('setup', $mailbox->id) }}" class="mt-3 block w-full rounded-2xl bg-stone-100 px-6 py-4 text-center text-lg font-bold transition hover:bg-stone-200">Configura tu móvil</a>
    <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Ir a mi cuenta</a></p>
</x-signup-layout>
