<x-mail::message>
# Correo en soporte@ sin ticket

**De:** {{ $sender ?: '(desconocido)' }}<br>
**Asunto:** {{ $originalSubject ?: '(sin asunto)' }}

<x-mail::panel>
{!! nl2br(e(\Illuminate\Support\Str::limit($body, 4000)), false) !!}
</x-mail::panel>

No es la respuesta de ningún ticket (no lleva un número válido, viene de otra dirección o es una respuesta automática). Sigue en la bandeja de entrada de soporte@. Si respondes a este aviso, le contestas directamente al remitente desde tu correo.
</x-mail::message>
