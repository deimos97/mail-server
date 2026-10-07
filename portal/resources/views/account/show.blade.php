<x-signup-layout title="Mi cuenta" :wide="true">
    <p class="mt-1 text-sm text-stone-500">Email de recuperación: <span data-ph-mask>{{ $user->email }}</span></p>

    @unless ($user->hasVerifiedEmail())
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Confirma tu email de recuperación para poder enviar correo.</p>
            <a href="{{ route('signup.verify') }}" class="mt-2 inline-block rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Confirmar ahora</a>
        </div>
    @endunless

    @isset($newDevice)
        {{-- La contraseña solo existe en esta respuesta: no se guarda en claro en ningún sitio --}}
        <div class="mt-6 rounded-3xl bg-tinta p-5 text-white sm:p-6" x-data="{ copied: false }" role="status">
            <p class="text-sm font-semibold uppercase tracking-wider text-amarillo-claro">Contraseña para «{{ $newDevice['device']->name }}»</p>
            <p class="mt-3 select-all font-mono text-2xl font-bold sm:text-3xl" data-ph-mask x-ref="pw" aria-label="Contraseña: {{ implode(' ', str_split($newDevice['password'], 4)) }}">@foreach (str_split($newDevice['password'], 4) as $group)<span class="mr-3 last:mr-0">{{ $group }}</span>@endforeach</p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" class="rounded-xl bg-white px-4 py-2 font-semibold text-tinta hover:bg-stone-200"
                        @click="navigator.clipboard.writeText(@js($newDevice['password'])); copied = true; setTimeout(() => copied = false, 2000)"
                        x-text="copied ? '¡Copiada!' : 'Copiar contraseña'">Copiar contraseña</button>
                <span class="text-sm text-white/70">Cópiala ahora: <strong>no volveremos a mostrarla</strong>.</span>
            </div>
            <dl class="mt-5 grid gap-x-6 gap-y-3 rounded-2xl bg-white/10 p-4 text-sm sm:grid-cols-2 [&>div]:min-w-0 [&_dd]:break-all">
                <div><dt class="text-white/60">Usuario</dt><dd class="font-mono" data-ph-mask>{{ $newDevice['mailbox']->email }}</dd></div>
                <div><dt class="text-white/60">Contraseña</dt><dd>la de arriba</dd></div>
                <div><dt class="text-white/60">Servidor de entrada (IMAP)</dt><dd class="font-mono">mail.{{ $newDevice['mailbox']->domain->name }} · 993 · SSL/TLS</dd></div>
                <div><dt class="text-white/60">Servidor de salida (SMTP)</dt><dd class="font-mono">mail.{{ $newDevice['mailbox']->domain->name }} · 465 · SSL/TLS</dd></div>
            </dl>
        </div>
    @endisset

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

            <div class="p-4 sm:p-5">
                <h2 class="font-bold">Dispositivos conectados</h2>
                <p class="text-sm text-stone-500">Cada móvil, ordenador o app de correo tiene su propia contraseña.</p>

                @if ($mailbox->appPasswords->isEmpty())
                    <p class="mt-3 text-sm text-stone-500">Todavía no has conectado ninguno.</p>
                @else
                    <ul class="mt-3 divide-y divide-stone-100">
                        @foreach ($mailbox->appPasswords as $device)
                            <li class="flex justify-between gap-3 py-2 text-sm">
                                <span class="font-medium">{{ $device->name }}</span>
                                <span class="text-stone-500">desde el {{ $device->created_at->timezone('Europe/Madrid')->format('d/m/Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('account.devices.store') }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <input type="hidden" name="mailbox" value="{{ $mailbox->id }}">
                    <label for="name-{{ $mailbox->id }}" class="sr-only">Nombre del dispositivo</label>
                    <input id="name-{{ $mailbox->id }}" name="name" type="text" maxlength="64" required placeholder="Por ejemplo: iPhone de Ana"
                           class="min-w-0 flex-1 rounded-2xl bg-stone-100 px-4 py-3 outline-none ring-2 ring-transparent placeholder:text-stone-400 focus:bg-white focus:ring-rojo/60">
                    <button type="submit" class="rounded-2xl bg-rojo px-5 py-3 font-bold text-white transition hover:bg-rojo-oscuro">Conectar un dispositivo</button>
                </form>
                <x-form-error name="name" />
                @isset($deviceError)<p class="mt-2 text-sm font-medium text-rojo">{{ $deviceError }}</p>@endisset
            </div>
        </article>
    @endforeach

    @if ($mailboxes->isEmpty())
        <p class="mt-6 text-stone-600">Todavía no tienes ningún buzón. <a href="{{ route('signup.plan') }}" class="font-semibold text-rojo underline">Termina el alta</a></p>
    @endif

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500">
            Webmail: <a href="https://webmail.{{ $mailboxes->first()?->domain->name ?? 'unagrandeylibre.es' }}" class="font-medium underline">webmail.{{ $mailboxes->first()?->domain->name ?? 'unagrandeylibre.es' }}</a>
            (de momento entra con tu dirección y la contraseña de un dispositivo; pronto, sin contraseña desde aquí)
        </p>
    </x-slot:after>
</x-signup-layout>
