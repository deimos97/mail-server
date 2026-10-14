{{-- Hilo de un ticket en el admin. Estilos en línea: el CSS de Filament no incluye las clases de la web. --}}
@php($ticket = $getRecord())
<div style="display: grid; gap: 1rem;">
    @foreach ($ticket->messages()->with(['attachments', 'staff'])->get() as $message)
        <x-filament::section
            :icon="$message->fromCustomer() ? 'heroicon-o-user' : 'heroicon-o-chat-bubble-left-right'"
            :icon-color="$message->fromCustomer() ? 'warning' : 'success'"
            compact
        >
            <x-slot name="heading">
                {{ $message->fromCustomer() ? 'Cliente' : 'Nosotros'.($message->staff ? ' ('.$message->staff->email.')' : '') }}
            </x-slot>
            <x-slot name="description">
                {{ $message->created_at->timezone('Europe/Madrid')->format('d/m/Y H:i') }}
                · {{ ['web' => 'formulario web', 'email' => 'por email', 'admin' => 'desde el admin'][$message->source] ?? $message->source }}
            </x-slot>

            <div style="white-space: pre-line; overflow-wrap: anywhere;">{{ $message->body }}</div>

            @if ($message->attachments->isNotEmpty())
                <div style="display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem;">
                    @foreach ($message->attachments as $attachment)
                        @php($url = route('filament.admin.support.attachment', $attachment))
                        <a href="{{ $url }}" target="_blank" title="{{ $attachment->name }} · {{ \App\Support\Bytes::format($attachment->size) }}">
                            @if (in_array($attachment->mime, ['image/heic', 'image/heif']))
                                {{-- Los navegadores (salvo Safari) no muestran HEIC: enlace para descargarla --}}
                                <span style="display: inline-block; padding: .5rem .75rem; border-radius: .5rem; border: 1px solid rgb(0 0 0 / .15);">{{ $attachment->name }}</span>
                            @else
                                <img src="{{ $url }}" alt="{{ $attachment->name }}" style="height: 9rem; max-width: 16rem; object-fit: cover; border-radius: .5rem; border: 1px solid rgb(0 0 0 / .1);">
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endforeach
</div>
