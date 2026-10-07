<x-signup-layout title="Mi cuenta" :wide="true">
    <p class="mt-1 text-sm text-stone-500">Email de recuperación: <span data-ph-mask>{{ $user->email }}</span></p>

    @unless ($user->hasVerifiedEmail())
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Confirma tu email de recuperación para poder enviar correo.</p>
            <a href="{{ route('signup.verify') }}" class="mt-2 inline-block rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Confirmar ahora</a>
        </div>
    @endunless


    @foreach ($mailboxes as $mailbox)
        <article class="mt-6 rounded-2xl ring-1 ring-stone-200">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 p-4 sm:p-5">
                <div class="min-w-0">
                    <p class="break-all text-lg font-bold" data-ph-mask>{{ $mailbox->email }}</p>
                    <p class="text-sm text-stone-500">Plan {{ $mailbox->plan?->name ?? '—' }}</p>
                </div>
                @if ($mailbox->status !== 'active')
                    <span class="rounded-full bg-rojo/10 px-3 py-1 text-sm font-semibold text-rojo">Suspendido</span>
                @elseif ($mailbox->can_send)
                    <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">Recibe y envía</span>
                @else
                    <span class="rounded-full bg-amarillo/25 px-3 py-1 text-sm font-semibold">Recibe · envío pendiente</span>
                @endif
            </header>

            @if ($mailbox->status === 'active')
                <div class="border-b border-stone-200 p-4 sm:p-5">
                    <a href="{{ route('account.webmail', $mailbox->id) }}" class="block rounded-2xl bg-rojo px-6 py-3.5 text-center text-lg font-bold text-white transition hover:bg-rojo-oscuro">
                        Abrir mi correo
                    </a>
                </div>
            @endif

            <div class="p-4 sm:p-5">
                <h2 class="font-bold">Dispositivos conectados</h2>
                <p class="text-sm text-stone-500">Cada móvil, ordenador o app de correo tiene su propia contraseña.</p>

                @if ($mailbox->appPasswords->isEmpty())
                    <p class="mt-3 text-sm text-stone-500">Todavía no has conectado ninguno.</p>
                @else
                    <ul class="mt-3 divide-y divide-stone-100">
                        @foreach ($mailbox->appPasswords as $device)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <span class="min-w-0">
                                    <span class="block font-medium">{{ $device->name }}</span>
                                    <span class="text-stone-500">desde el {{ $device->created_at->timezone('Europe/Madrid')->format('d/m/Y') }}</span>
                                </span>
                                <form method="POST" action="{{ route('account.devices.revoke', $device->id) }}"
                                      x-data @submit="if (! confirm(@js('¿Desconectar «'.$device->name.'»? Dejará de poder entrar en tu correo.'))) $event.preventDefault()">
                                    @csrf
                                    <button class="rounded-xl px-3 py-1.5 font-semibold text-rojo ring-1 ring-rojo/30 hover:bg-rojo/5">Revocar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a href="{{ route('setup', $mailbox->id) }}" class="mt-4 block rounded-2xl px-5 py-3 text-center font-bold ring-2 ring-rojo/60 transition hover:bg-rojo/5">Configura un dispositivo</a>
            </div>
        </article>
    @endforeach

    @if ($mailboxes->isEmpty())
        <p class="mt-6 text-stone-600">Todavía no tienes ningún buzón. <a href="{{ route('signup.plan') }}" class="font-semibold text-rojo underline">Termina el alta</a></p>
    @endif

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500">
            ¿Cómo configurarlo en cada app? <a href="{{ route('help.setup') }}" class="font-medium underline">Guías</a>. También puedes entrar al webmail en <a href="https://webmail.{{ $mailboxes->first()?->domain->name ?? 'unagrandeylibre.es' }}" class="font-medium underline">webmail.{{ $mailboxes->first()?->domain->name ?? 'unagrandeylibre.es' }}</a>
            con tu dirección y la contraseña de un dispositivo.
        </p>
    </x-slot:after>
</x-signup-layout>
