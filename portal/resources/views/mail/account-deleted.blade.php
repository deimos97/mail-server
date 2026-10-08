<x-mail::message>
# Hemos borrado tu cuenta

Como nos pediste, hemos borrado tu cuenta de unagrandeylibre.es@if (count($addresses)) y todo el correo de {{ implode(', ', $addresses) }}@endif.

Durante los próximos 90 días nadie podrá registrar {{ count($addresses) === 1 ? 'esa dirección' : 'esas direcciones' }}. Después quedarán libres.

Si no has sido tú, escríbenos cuanto antes a {{ $support }}.

Gracias por haber confiado en nosotros.<br>
Una Grande y Libre
</x-mail::message>
