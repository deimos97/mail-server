<x-mail::message>
@if ($level >= 100)
# Tu buzón está lleno

**{{ $address }}** ha llegado a sus {{ $quota }}. Mientras siga lleno, **los correos nuevos se rechazan** y quien te escriba recibirá un aviso de que no se ha podido entregar.
@else
# Tu buzón está casi lleno

**{{ $address }}** ya usa más del {{ $level }} % de sus {{ $quota }}. Cuando se llene, los correos nuevos se rechazarán.
@endif

@if ($free && $upsellName)
Con el plan {{ $upsellName }} tienes {{ $upsellQuota }} y más envíos al día. O, si lo prefieres, borra correos con adjuntos grandes y vacía la papelera.
@else
Puedes pasar a un plan con más espacio, o borrar correos con adjuntos grandes y vaciar la papelera.
@endif

<x-mail::button :url="route('account.plan', $mailboxId)" color="primary">
{{ $free ? 'Ver planes' : 'Cambiar de plan' }}
</x-mail::button>

Si tienes dudas, escríbenos a {{ $support }}.

Un saludo,<br>
Una Grande y Libre
</x-mail::message>
