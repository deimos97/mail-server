<x-mail::message>
{!! nl2br(e($message->body), false) !!}

Un saludo,<br>
{{ config('support.from.name') }}

<x-mail::subcopy>
Consulta {{ $ticket->number() }}: {{ $ticket->subject }}. Para contestar, responde a este correo sin cambiar el asunto.
</x-mail::subcopy>
</x-mail::message>
