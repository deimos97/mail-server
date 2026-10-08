<x-mail::message>
# Tu copia del correo está lista

Ya puedes descargar la copia de **{{ $address }}** desde tu cuenta:

<x-mail::button :url="route('account')" color="primary">
Ir a mi cuenta
</x-mail::button>

Es un archivo .zip con un fichero .mbox por carpeta (Bandeja de entrada, Enviados…), que puedes abrir con Thunderbird, Apple Mail y la mayoría de programas de correo.

Estará disponible hasta el {{ $expiresAt->timezone('Europe/Madrid')->format('d/m/Y \a \l\a\s H:i') }}. Después la borramos.

Si no la has pedido tú, cambia tu contraseña.

Un saludo,<br>
Una Grande y Libre
</x-mail::message>
