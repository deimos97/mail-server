<x-signup-layout title="Configura un dispositivo">
    <p class="mt-2 text-stone-600">¿Dónde quieres usar <strong data-ph-mask>{{ $mailbox->email }}</strong>? Crearemos una contraseña solo para ese dispositivo.</p>

    @isset($error)<p class="mt-4 rounded-2xl bg-rojo/10 px-4 py-3 text-sm font-medium text-rojo">{{ $error }}</p>@endisset

    <form method="POST" action="{{ route('setup.store', $mailbox->id) }}" class="mt-6" x-data="{ client: null }">
        @csrf
        <fieldset class="grid gap-3 sm:grid-cols-2">
            <legend class="sr-only">App</legend>
            @foreach ($clients as $key => $client)
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl p-4 ring-2 transition hover:bg-stone-50"
                       :class="client === @js($key) ? 'ring-rojo bg-rojo/5' : 'ring-stone-200'">
                    <input type="radio" name="client" value="{{ $key }}" x-model="client" class="size-5 accent-rojo" required>
                    <span class="font-semibold">{{ $client['label'] }}</span>
                </label>
            @endforeach
        </fieldset>
        <x-form-error name="client" />

        <div x-cloak x-show="client" class="mt-5">
            <label for="name" class="block font-semibold">Nombre del dispositivo <span class="font-normal text-stone-500">(opcional)</span></label>
            <input id="name" name="name" type="text" maxlength="64" placeholder="Por ejemplo: iPhone de Ana"
                   class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3 outline-none ring-2 ring-transparent placeholder:text-stone-400 focus:bg-white focus:ring-rojo/60">
            <button type="submit" class="mt-5 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Continuar</button>
        </div>
    </form>

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
