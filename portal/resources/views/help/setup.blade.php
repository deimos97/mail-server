<x-signup-layout title="Configura tu correo" :wide="true">
    <p class="mt-2 text-stone-600">Tu correo @unagrandeylibre.es funciona en el webmail y en cualquier app de correo. Elige la tuya:</p>
    <ul class="mt-6 grid gap-3 sm:grid-cols-2">
        @foreach ($clients as $key => $client)
            <li><a href="{{ route('help.setup.client', $key) }}" class="block rounded-2xl p-4 font-semibold ring-2 ring-stone-200 transition hover:bg-stone-50 hover:ring-rojo/60">{{ $client['label'] }}</a></li>
        @endforeach
    </ul>
    <p class="mt-6 text-sm text-stone-500">Cada dispositivo usa su propia contraseña, que creas en <a href="{{ route('account') }}" class="font-medium underline">Mi cuenta</a>. Así, si pierdes el móvil, lo desconectas sin tocar los demás.</p>
</x-signup-layout>
