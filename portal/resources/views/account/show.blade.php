<x-signup-layout title="Mi cuenta" :wide="true">
    <p class="mt-1 text-sm text-stone-500">Email de recuperación: <span data-ph-mask>{{ $user->email }}</span> · <a href="{{ route('account.email') }}" class="font-medium underline">Cambiar</a></p>

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

            @php($subscription = $subscriptions->get('mailbox:'.$mailbox->id))
            @php($state = $states->get($mailbox->id))
            @if ($state?->unpaid_since)
                <div class="border-b border-stone-200 bg-rojo/5 p-4 text-sm sm:p-5">
                    <p class="font-semibold text-rojo">
                        @if ($mailbox->status === 'suspended')
                            Suspendido por falta de pago: sigue recibiendo correo, pero no puedes entrar ni enviar.
                        @else
                            No hemos podido cobrar el plan.
                        @endif
                        Si no se paga, el {{ $state->unpaid_since->copy()->addDays(config('lifecycle.unpaid.delete_after_days'))->timezone('Europe/Madrid')->format('d/m/Y') }} borraremos este buzón.
                    </p>
                    @if ($subscription && ! $subscription->ended())
                        <form method="POST" action="{{ route('account.billing') }}" class="mt-3">@csrf
                            <button class="rounded-xl bg-rojo px-4 py-2 font-semibold text-white hover:bg-rojo-oscuro">Actualizar la tarjeta y pagar</button>
                        </form>
                    @else
                        <a href="{{ route('signup', ['nuevo' => 1]) }}" class="mt-3 inline-block font-semibold text-rojo underline">Contratar un plan</a>
                    @endif
                </div>
            @elseif ($subscription && ! $subscription->ended())
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 p-4 text-sm sm:p-5">
                    <p class="text-stone-600">
                        @if ($subscription->onGracePeriod())
                            Plan cancelado: seguirá activo hasta el {{ $subscription->ends_at->timezone('Europe/Madrid')->format('d/m/Y') }}.
                        @else
                            Plan de pago activo. Se renueva solo; puedes cancelarlo cuando quieras.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('account.billing') }}">@csrf
                        <button class="rounded-xl px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Gestionar pago, facturas y plan</button>
                    </form>
                </div>
            @endif

            @php($percent = $mailbox->usedPercent())
            <div class="border-b border-stone-200 p-4 sm:p-5">
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    <span class="font-semibold">Espacio usado</span>
                    <span class="text-stone-600">{{ \App\Support\Bytes::format($mailbox->usedBytes()) }} de {{ \App\Support\Bytes::format($mailbox->quota_bytes) }}</span>
                </div>
                @if ($percent !== null)
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Espacio usado">
                        <div class="h-full rounded-full {{ $percent >= 90 ? 'bg-rojo' : 'bg-amarillo' }}" style="width: {{ max($percent, 1) }}%"></div>
                    </div>
                    @if ($percent >= 90)
                        <p class="mt-2 text-sm font-semibold text-rojo">Casi no te queda espacio: cuando se llene, dejarás de recibir correo. Borra correos grandes o vacía la papelera.</p>
                    @endif
                @endif
            </div>

            @if ($mailbox->status === 'active')
                <div class="border-b border-stone-200 p-4 sm:p-5">
                    <a href="{{ route('account.webmail', $mailbox->id) }}" class="block rounded-2xl bg-rojo px-6 py-3.5 text-center text-lg font-bold text-white transition hover:bg-rojo-oscuro">
                        Abrir mi correo
                    </a>
                    @if ($webmailAt = $mailbox->lastLoginOf(0))
                        <p class="mt-2 text-center text-sm text-stone-500">Último acceso al webmail: {{ $webmailAt->locale(app()->getLocale())->diffForHumans() }}</p>
                    @endif
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
                            @php($usedAt = $mailbox->lastLoginOf($device->id))
                            <li class="py-2 text-sm" x-data="{ editing: false }">
                                <div class="flex items-center justify-between gap-3" x-show="! editing">
                                    <span class="min-w-0">
                                        <span class="block font-medium">{{ $device->name }}</span>
                                        <span class="text-stone-500">
                                            desde el {{ $device->created_at->timezone('Europe/Madrid')->format('d/m/Y') }} ·
                                            {{ $usedAt ? 'último uso '.$usedAt->locale(app()->getLocale())->diffForHumans() : 'todavía no se ha usado' }}
                                        </span>
                                    </span>
                                    <span class="flex shrink-0 gap-2">
                                        <button type="button" @click="editing = true; $nextTick(() => $refs.name.focus())" class="rounded-xl px-3 py-1.5 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Renombrar</button>
                                        <form method="POST" action="{{ route('account.devices.revoke', $device->id) }}"
                                              @submit="if (! confirm(@js('¿Desconectar «'.$device->name.'»? Dejará de poder entrar en tu correo.'))) $event.preventDefault()">
                                            @csrf
                                            <button class="rounded-xl px-3 py-1.5 font-semibold text-rojo ring-1 ring-rojo/30 hover:bg-rojo/5">Revocar</button>
                                        </form>
                                    </span>
                                </div>
                                <form method="POST" action="{{ route('account.devices.rename', $device->id) }}" class="flex gap-2" x-show="editing" x-cloak>
                                    @csrf
                                    <label class="sr-only" for="device-name-{{ $device->id }}">Nombre del dispositivo</label>
                                    <input id="device-name-{{ $device->id }}" name="name" x-ref="name" value="{{ $device->name }}" maxlength="64" required
                                           class="min-w-0 flex-1 rounded-xl border-stone-300 px-3 py-1.5" @keydown.escape="editing = false">
                                    <button class="rounded-xl bg-rojo px-3 py-1.5 font-semibold text-white hover:bg-rojo-oscuro">Guardar</button>
                                    <button type="button" @click="editing = false" class="rounded-xl px-3 py-1.5 font-semibold ring-1 ring-stone-300">Cancelar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a href="{{ route('setup', $mailbox->id) }}" class="mt-4 block rounded-2xl px-5 py-3 text-center font-bold ring-2 ring-rojo/60 transition hover:bg-rojo/5">Configura un dispositivo</a>
            </div>

            @php($export = $exports->get($mailbox->id))
            <div class="border-t border-stone-200 p-4 text-sm sm:p-5">
                <h2 class="font-bold">Copia de tu correo</h2>
                @if ($export?->isDownloadable())
                    <p class="mt-1 text-stone-500">Lista: {{ \App\Support\Bytes::format($export->size_bytes) }}, disponible hasta el {{ $export->expires_at->timezone('Europe/Madrid')->format('d/m/Y H:i') }}.</p>
                    <a href="{{ route('account.export.download', $export->id) }}" class="mt-3 inline-block rounded-xl bg-rojo px-4 py-2 font-semibold text-white hover:bg-rojo-oscuro">Descargar la copia (.zip)</a>
                @elseif ($export?->status === 'pending')
                    <p class="mt-1 text-stone-500">La estamos preparando. Te avisaremos por email en cuanto esté lista.</p>
                @else
                    <p class="mt-1 text-stone-500">
                        Todo tu correo en un .zip, con un archivo .mbox por carpeta, que puedes abrir con Thunderbird o Apple Mail.
                        @if ($export?->status === 'failed') <span class="font-semibold text-rojo">La última vez no se pudo preparar; vuelve a intentarlo.</span> @endif
                    </p>
                    <form method="POST" action="{{ route('account.export', $mailbox->id) }}" class="mt-3">
                        @csrf
                        <button class="rounded-xl px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Descargar una copia</button>
                    </form>
                @endif
            </div>
        </article>
    @endforeach

    @if ($mailboxes->isNotEmpty())
        <div class="mt-6 rounded-2xl border-2 border-dashed border-stone-300 p-4 text-center sm:p-5">
            <a href="{{ route('signup', ['nuevo' => 1]) }}" class="font-bold text-rojo underline">Añadir otro buzón</a>
            <p class="mt-1 text-sm text-stone-500">Tu cuenta incluye un buzón gratis; los demás van con un plan de pago.</p>
        </div>
    @endif

    @if ($mailboxes->isEmpty())
        <p class="mt-6 text-stone-600">Todavía no tienes ningún buzón. <a href="{{ route('signup.plan') }}" class="font-semibold text-rojo underline">Termina el alta</a></p>
    @endif

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500">
            ¿Cómo configurarlo en cada app? <a href="{{ route('help.setup') }}" class="font-medium underline">Guías</a>. También puedes entrar al webmail en <a href="https://webmail.{{ $mailboxes->first()?->domain->name ?? 'unagrandeylibre.es' }}" class="font-medium underline">webmail.{{ $mailboxes->first()?->domain->name ?? 'unagrandeylibre.es' }}</a>
            con tu dirección y la contraseña de un dispositivo.
        </p>
        <p class="mt-6 text-center text-sm"><a href="{{ route('account.delete') }}" class="text-stone-500 underline hover:text-rojo">Borrar mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
