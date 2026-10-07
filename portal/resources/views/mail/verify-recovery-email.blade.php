<x-mail::message>
# Confirma tu email

Escribe este código en la página del alta:

<x-mail::panel>
<span style="font-size: 28px; font-weight: 700; letter-spacing: 6px;">{{ $code }}</span>
</x-mail::panel>

O pulsa el botón:

<x-mail::button :url="$link" color="primary">
Confirmar mi email
</x-mail::button>

Hasta que lo confirmes, tu nueva dirección ya recibe correo pero todavía no puede enviarlo. El código y el enlace caducan en una hora.

Si no has creado una cuenta en unagrandeylibre.es, ignora este mensaje.

Un saludo,<br>
Una Grande y Libre
</x-mail::message>
