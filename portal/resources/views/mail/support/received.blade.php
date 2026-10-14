<x-mail::message>
# Hemos recibido tu mensaje

Gracias por escribirnos. Lo hemos registrado con el número **{{ $ticket->number() }}** y te contestaremos a este email lo antes posible.

Si quieres añadir algo, **responde a este correo** sin cambiar el asunto y lo uniremos a tu consulta.

<x-mail::panel>
**{{ $ticket->subject }}**

{!! nl2br(e($message->body), false) !!}
</x-mail::panel>

Un saludo,<br>
Javier · Una Grande y Libre
</x-mail::message>
