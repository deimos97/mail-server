<x-signup-layout title="¿Qué correo quieres abrir?">
    <form method="POST" action="{{ route('oauth.choose') }}" class="mt-6 space-y-3">
        @csrf
        @foreach ($params as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        @foreach ($mailboxes as $mailbox)
            <button name="mailbox" value="{{ $mailbox->id }}" class="block w-full rounded-2xl p-4 text-left font-semibold ring-2 ring-stone-200 transition hover:bg-stone-50 hover:ring-rojo/60" data-ph-mask>
                {{ $mailbox->email }}
            </button>
        @endforeach
    </form>
</x-signup-layout>
