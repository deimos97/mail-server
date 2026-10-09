@php
    $input = 'w-full rounded-2xl bg-stone-100 px-4 py-3 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60';
@endphp
<x-signup-layout title="Seguridad">
    <p class="mt-2 text-stone-600">Protege tu cuenta de la web (la de «Mi cuenta»). Tus apps de correo siguen usando sus contraseñas de dispositivo.</p>

    {{-- Códigos de recuperación: solo se muestran una vez --}}
    @if ($recoveryCodes)
        <div class="mt-6 rounded-3xl bg-tinta p-5 text-white" x-data="{ copied: false }">
            <p class="text-sm font-semibold uppercase tracking-wider text-amarillo-claro">Tus códigos de recuperación</p>
            <p class="mt-2 text-sm text-white/80">Si pierdes el móvil, cada código te deja entrar una vez. Guárdalos en un sitio seguro: <strong>no volveremos a mostrarlos</strong>.</p>
            <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-lg" data-ph-mask>
                @foreach ($recoveryCodes as $code)<li>{{ $code }}</li>@endforeach
            </ul>
            <button type="button" class="mt-4 rounded-xl bg-white px-4 py-2 font-semibold text-tinta hover:bg-stone-200"
                    @click="navigator.clipboard.writeText(@js(implode("\n", $recoveryCodes))); copied = true"
                    x-text="copied ? '¡Copiados!' : 'Copiar los códigos'">Copiar los códigos</button>
        </div>
    @endif

    {{-- Verificación en dos pasos --}}
    <section class="mt-8">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-extrabold tracking-tight">Verificación en dos pasos</h2>
            @if ($user->hasTwoFactor())
                <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">Activada</span>
            @endif
        </div>
        <p class="mt-1 text-sm text-stone-600">Además de la contraseña, al entrar te pediremos un código de una app como Google Authenticator, 1Password o Authy.</p>

        @if ($pending)
            <div class="mt-4 rounded-2xl ring-1 ring-stone-200 p-4">
                <p class="font-semibold">1. Escanea este código con tu app</p>
                <img src="{{ $qr }}" alt="Código QR para la app de autenticación" class="mx-auto mt-3 size-48 rounded-xl bg-white p-2 ring-1 ring-stone-200">
                <p class="mt-3 text-sm text-stone-600">¿No puedes escanearlo? Escribe esta clave en la app:</p>
                <x-copy-field :value="$pending" label="Clave" mask class="mt-2" />
                <form method="POST" action="{{ route('account.security.2fa.confirm') }}" class="mt-5">
                    @csrf
                    <label for="code" class="block font-semibold">2. Escribe el código de 6 cifras que te da</label>
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus
                           class="mt-2 {{ $input }} text-center font-mono tracking-[0.4em]">
                    <x-form-error name="code" />
                    <button class="mt-4 w-full rounded-2xl bg-rojo px-6 py-3.5 font-bold text-white hover:bg-rojo-oscuro">Activar</button>
                </form>
                <form method="POST" action="{{ route('account.security.2fa.cancel') }}" class="mt-3 text-center text-sm">@csrf
                    <button class="font-semibold text-stone-600 underline">Cancelar</button>
                </form>
            </div>
        @elseif ($user->hasTwoFactor())
            <p class="mt-2 text-sm text-stone-600">Te quedan {{ count($user->two_factor_recovery_codes ?? []) }} códigos de recuperación.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2" x-data="{ action: null }">
                <button type="button" @click="action = 'codes'" class="rounded-xl px-4 py-2.5 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Nuevos códigos de recuperación</button>
                <button type="button" @click="action = 'disable'" class="rounded-xl px-4 py-2.5 font-semibold text-rojo ring-1 ring-rojo/40 hover:bg-rojo/5">Desactivar</button>
                <form method="POST" x-show="action" x-cloak class="sm:col-span-2"
                      :action="action === 'codes' ? @js(route('account.security.2fa.codes')) : @js(route('account.security.2fa.disable'))">
                    @csrf
                    <label for="pw-2fa" class="block text-sm font-semibold">Confirma con tu contraseña de la web</label>
                    <input id="pw-2fa" name="password" type="password" autocomplete="current-password" required class="mt-2 {{ $input }}">
                    <button class="mt-3 w-full rounded-2xl bg-rojo px-6 py-3 font-bold text-white hover:bg-rojo-oscuro"
                            x-text="action === 'codes' ? 'Generar códigos nuevos' : 'Desactivar la verificación en dos pasos'"></button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('account.security.2fa.start') }}" class="mt-4" x-data="{ open: false }">
                @csrf
                <button type="button" x-show="! open" @click="open = true; $nextTick(() => $refs.pw.focus())" class="rounded-xl bg-rojo px-4 py-2.5 font-semibold text-white hover:bg-rojo-oscuro">Activar</button>
                <div x-show="open" x-cloak>
                    <label for="pw-start" class="block text-sm font-semibold">Confirma con tu contraseña de la web</label>
                    <input id="pw-start" x-ref="pw" name="password" type="password" autocomplete="current-password" required class="mt-2 {{ $input }}">
                    <button class="mt-3 w-full rounded-2xl bg-rojo px-6 py-3 font-bold text-white hover:bg-rojo-oscuro">Continuar</button>
                </div>
            </form>
        @endif
        <x-form-error name="password" />
    </section>

    {{-- Passkeys --}}
    <section class="mt-10 border-t border-stone-200 pt-8">
        <h2 class="text-xl font-extrabold tracking-tight">Passkeys</h2>
        <p class="mt-1 text-sm text-stone-600">Entra sin contraseña con la huella, la cara o el PIN de tu móvil u ordenador. Más cómodo y más seguro.</p>

        @if ($passkeys->isNotEmpty())
            <ul class="mt-4 divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
                @foreach ($passkeys as $passkey)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <span class="min-w-0">
                            <span class="block font-semibold">{{ $passkey->name }}</span>
                            <span class="text-stone-500">desde el {{ $passkey->created_at->timezone('Europe/Madrid')->format('d/m/Y') }}{{ $passkey->last_used_at ? ' · último uso '.$passkey->last_used_at->locale('es')->diffForHumans() : '' }}</span>
                        </span>
                        <form method="POST" action="{{ route('account.security.passkeys.destroy', $passkey->id) }}"
                              x-data @submit="if (! confirm(@js('¿Quitar la passkey «'.$passkey->name.'»?'))) $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button class="rounded-xl px-3 py-1.5 font-semibold text-rojo ring-1 ring-rojo/30 hover:bg-rojo/5">Quitar</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4" x-data="passkeyCreate(@js(route('account.security.passkeys.options')), @js(route('account.security.passkeys.store')))">
            <template x-if="supported">
                <div>
                    <label for="pk-name" class="block text-sm font-semibold">Nombre de la passkey</label>
                    <div class="mt-2 flex gap-2">
                        <input id="pk-name" x-model="name" maxlength="64" placeholder="Ej.: iPhone de Ana" class="min-w-0 flex-1 rounded-2xl bg-stone-100 px-4 py-3 outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60" @keydown.enter.prevent="create()">
                        <button type="button" @click="create()" :disabled="busy" class="shrink-0 rounded-2xl bg-rojo px-4 py-3 font-semibold text-white hover:bg-rojo-oscuro disabled:opacity-60" x-text="busy ? 'Creando…' : 'Añadir'">Añadir</button>
                    </div>
                    <p x-show="error" x-text="error" class="mt-2 text-sm font-medium text-rojo" role="alert"></p>
                </div>
            </template>
            <p x-show="! supported" class="text-sm text-stone-500">Este navegador no admite passkeys.</p>
        </div>
    </section>

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
