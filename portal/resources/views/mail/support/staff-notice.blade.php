<x-mail::message>
# {{ $isNew ? 'Ticket nuevo' : 'Ha respondido el cliente' }} · {{ $ticket->number() }}

**De:** {{ $ticket->email }}{{ $ticket->user_id ? ' (con cuenta)' : ' (sin sesión)' }}<br>
**Asunto:** {{ $ticket->subject }}

<x-mail::panel>
{!! nl2br(e($message->body), false) !!}
</x-mail::panel>

@if ($message->attachments->isNotEmpty())
{{ trans_choice('{1} 1 imagen adjunta|[2,*] :count imágenes adjuntas', $message->attachments->count()) }}.
@endif

<x-mail::button :url="$url" color="primary">
Abrir en el admin
</x-mail::button>
</x-mail::message>
