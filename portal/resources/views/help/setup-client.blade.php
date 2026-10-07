<x-signup-layout :title="'Configurar el correo en '.$label">
    <div class="mt-5">
        @include('setup.steps.'.$client, ['email' => null, 'password' => null, 'profileUrl' => null])
    </div>
    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('help.setup') }}" class="font-medium underline">Otras apps</a></p>
    </x-slot:after>
</x-signup-layout>
