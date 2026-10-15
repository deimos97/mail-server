<x-mail::message>
@switch($type)
@case('payment_failed')
# No hemos podido cobrar tu plan

No hemos podido cobrar el plan de **{{ $address }}**. Stripe volverá a intentarlo en los próximos días, pero lo más rápido es que revises tu tarjeta.

Si no se resuelve, a los 10 días suspenderemos el buzón (seguirá recibiendo correo, pero no podrás entrar ni enviar) y a los 30 lo borraremos.
@break
@case('cancelled')
# Has cancelado tu plan

Has cancelado el plan de **{{ $address }}**. Como este buzón no puede pasar al plan gratis (ya tienes uno, ocupa más de lo que permite o es un nombre corto), **lo perderás**:

- Sigue funcionando con normalidad hasta el **{{ $date }}**.
- Después lo suspenderemos (seguirá recibiendo correo, pero no podrás entrar ni enviar).
- El **{{ $deleteDate }}** borraremos todo su correo y, más adelante, el nombre quedará libre para otras personas.

¿Ha sido un error? Puedes deshacerlo hasta el {{ $date }} en tu cuenta → «Cambiar de plan». Si quieres conservar el correo, descarga antes una copia.
@break
@case('ended')
# Tu plan ha terminado

El plan de **{{ $address }}** ha terminado y el buzón no puede pasar al plan gratis (ya tienes uno, ocupa más de lo que permite o es un nombre corto).

Si quieres conservarlo, vuelve a contratar un plan. Si no, a los 10 días lo suspenderemos y a los 30 lo borraremos.
@break
@case('downgraded')
# Tu correo ha pasado al plan gratis

El plan de pago de **{{ $address }}** ha terminado y el buzón sigue funcionando con el plan gratis, con todo su correo.
@if ($aliases)

El plan gratis no tiene alias, así que **{{ implode(', ', $aliases) }}** {{ count($aliases) === 1 ? 'ha dejado' : 'han dejado' }} de funcionar. Te guardamos {{ count($aliases) === 1 ? 'el nombre' : 'los nombres' }} unos meses: si vuelves a un plan de pago, puedes recuperarlos desde tu cuenta.
@endif
@break
@case('aliases_trimmed')
# Algunos alias ya no funcionan

El plan de **{{ $address }}** ahora tiene menos alias, así que **{{ implode(', ', $aliases) }}** {{ count($aliases) === 1 ? 'ha dejado' : 'han dejado' }} de funcionar (eran los más nuevos). Te guardamos {{ count($aliases) === 1 ? 'el nombre' : 'los nombres' }} unos meses: si vuelves a un plan con más alias, puedes recuperarlos desde tu cuenta.
@break
@case('reactivated')
# Tu correo vuelve a funcionar

Hemos recibido el pago: **{{ $address }}** vuelve a funcionar con normalidad.
@break
@case('suspended')
# Hemos suspendido tu correo

Como no hemos podido cobrar el plan, **{{ $address }}** está suspendido: sigue recibiendo correo, pero no puedes entrar ni enviar.

Paga desde tu cuenta para reactivarlo al momento. Si no, el {{ $date }} lo borraremos.
@break
@case('deletion_warning')
# Vamos a borrar tu correo dentro de una semana

El {{ $date }} borraremos todo el correo de **{{ $address }}** porque el plan sigue sin pagarse. Si quieres conservarlo, paga desde tu cuenta o descarga antes una copia.
@break
@case('deleted')
# Hemos borrado tu correo

Hemos borrado **{{ $address }}** y todo su correo porque el plan no se ha pagado. Durante un tiempo nadie podrá registrar esa dirección.
@break
@case('inactive_warning')
# Hace mucho que no usas tu correo

Hace más de 6 meses que nadie entra en **{{ $address }}**. Los buzones gratis sin uso se borran para liberar los nombres.

Si quieres conservarlo, entra en tu correo antes del {{ $date }}. Si no, lo borraremos.
@break
@case('inactive_deleted')
# Hemos borrado tu correo por inactividad

Como nadie ha entrado en **{{ $address }}** en más de 6 meses, lo hemos borrado junto con su correo.
@break
@endswitch

<x-mail::button :url="route('account')" color="primary">
Ir a mi cuenta
</x-mail::button>

Si tienes dudas, escríbenos a {{ $support }}.

Un saludo,<br>
Una Grande y Libre
</x-mail::message>
