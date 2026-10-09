<x-signup-layout :title="'Configura '.$label">
    @unless ($profileUrl)
        {{-- La contraseña solo existe en esta respuesta --}}
        <div class="mt-5 rounded-3xl bg-tinta p-5 text-white" x-data="{ copied: false }">
            <p class="text-sm font-semibold uppercase tracking-wider text-amarillo-claro">Tu contraseña para «{{ $device->name }}»</p>
            <p class="mt-3 select-all font-mono text-2xl font-bold sm:text-3xl" data-ph-mask aria-label="Contraseña: {{ implode(' ', str_split($password, 4)) }}">@foreach (str_split($password, 4) as $group)<span class="mr-3 last:mr-0">{{ $group }}</span>@endforeach</p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" class="rounded-xl bg-white px-4 py-2 font-semibold text-tinta hover:bg-stone-200"
                        @click="navigator.clipboard.writeText(@js($password)); copied = true; setTimeout(() => copied = false, 2000)"
                        x-text="copied ? '¡Copiada!' : 'Copiar contraseña'">Copiar contraseña</button>
                <span class="text-sm text-white/70">Cópiala ahora: <strong>no volveremos a mostrarla</strong>.</span>
            </div>
        </div>
    @endunless

    <div class="mt-6">
        @include('setup.steps.'.$client, ['email' => $mailbox->email, 'password' => $password, 'profileUrl' => $profileUrl])
    </div>

    @unless ($profileUrl)
        <x-ai-help :mailbox="$mailbox" :client="$client" />
    @endunless

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
