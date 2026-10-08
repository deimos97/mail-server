<x-signup-layout title="Borrar mi cuenta">
    <div class="mt-4 rounded-2xl bg-rojo/10 p-4 text-sm">
        <p class="font-semibold text-rojo">Esto no se puede deshacer.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-stone-700">
            <li>Borramos al momento todo tu correo{{ $mailboxes->isNotEmpty() ? ' de ' : '' }}<span data-ph-mask>{{ $mailboxes->implode(', ') }}</span>. No podremos recuperarlo.</li>
            <li>{{ $mailboxes->count() === 1 ? 'La dirección deja' : 'Las direcciones dejan' }} de recibir correo y tus dispositivos se desconectan.</li>
            <li>Durante 90 días nadie podrá registrar {{ $mailboxes->count() === 1 ? 'esa dirección' : 'esas direcciones' }}; después quedarán libres.</li>
        </ul>
        <p class="mt-3 text-stone-700">Si quieres conservar tu correo, <a href="{{ route('account') }}" class="font-semibold underline">descarga antes una copia</a> desde Mi cuenta.</p>
    </div>

    <form method="POST" action="{{ route('account.delete.destroy') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="password" class="block font-semibold">Tu contraseña de la web</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <x-form-error name="password" />
        </div>
        <div>
            <label class="flex items-start gap-3">
                <input type="checkbox" name="confirm" value="1" class="mt-1 size-5 accent-rojo" required>
                <span>Entiendo que se borrarán mi cuenta y todo mi correo, y que no se puede deshacer.</span>
            </label>
            <x-form-error name="confirm" />
        </div>
        <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">Borrar mi cuenta para siempre</button>
    </form>

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">No, volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
