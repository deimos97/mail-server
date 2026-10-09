<x-mail::message>
@switch($type)
@case('two_factor_on')
# Verificación en dos pasos activada

A partir de ahora, para entrar en tu cuenta de unagrandeylibre.es te pediremos también el código de tu app de autenticación. Guarda tus códigos de recuperación en un sitio seguro.
@break
@case('two_factor_off')
# Verificación en dos pasos desactivada

Tu cuenta de unagrandeylibre.es ya no pide el código de la app al entrar.
@break
@case('passkey_added')
# Nueva passkey en tu cuenta

Se ha añadido la passkey «{{ $detail }}» a tu cuenta de unagrandeylibre.es. Con ella puedes entrar sin contraseña.
@break
@endswitch

**Si no has sido tú**, cambia tu contraseña cuanto antes desde la web y escríbenos a {{ $support }}.

Un saludo,<br>
Una Grande y Libre
</x-mail::message>
