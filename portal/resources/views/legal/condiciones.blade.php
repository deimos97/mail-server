@php $u = config('lifecycle.unpaid'); $i = config('lifecycle.inactive_free'); @endphp
<x-legal-page :title="$title">
    <p>Estas condiciones regulan el uso del servicio de correo {{ $l['brand'] }}, que presta {{ $l['company'] }} (NIF {{ $l['nif'] }}, {{ $l['address'] }}). Al crear una cuenta las aceptas, junto con la <a href="{{ route('legal', 'uso-aceptable') }}">política de uso aceptable</a> y la <a href="{{ route('legal', 'privacidad') }}">política de privacidad</a>.</p>

    <h2>1. El servicio</h2>
    <p>Te damos una dirección de correo @{{ $l['brand'] }} con su buzón, acceso por webmail y por aplicaciones de correo (IMAP y SMTP), y un área «Mi cuenta» para gestionarla. Lo que incluye cada plan (espacio, envíos por hora y demás) se indica en la web en el momento de contratarlo.</p>

    <h2>2. Tu cuenta</h2>
    <ul>
        <li>Debes tener al menos 14 años; para contratar un plan de pago, ser mayor de edad.</li>
        <li>Necesitamos un <strong>email de recuperación</strong> válido: es al que enviamos los avisos importantes y el enlace para recuperar la contraseña. Mantenlo al día.</li>
        <li>Eres responsable de guardar tus contraseñas. Cada aplicación o dispositivo tiene la suya y puedes revocarla desde «Mi cuenta».</li>
        <li>Cada cuenta incluye <strong>un buzón gratis</strong>; los demás buzones van con un plan de pago.</li>
    </ul>

    <h2>3. Plan gratis</h2>
    <ul>
        <li>No caduca mientras lo uses. Hasta que confirmes tu email de recuperación, el buzón recibe correo pero no puede enviarlo.</li>
        <li>Si pasan {{ $i['after_months'] }} meses sin que nadie entre en el buzón, te avisaremos; si no vuelves en {{ $i['grace_days'] }} días, lo borraremos con todo su contenido.</li>
        <li>Los nombres cortos (con sobrecoste) solo están disponibles con planes de pago.</li>
    </ul>

    <h2>4. Planes de pago</h2>
    <ul>
        <li><strong>Precios:</strong> los que se muestran incluyen el IVA. Se cobran por adelantado, al mes o al año según el plan, y se <strong>renuevan solos</strong> hasta que los canceles.</li>
        <li><strong>Pago:</strong> con tarjeta u otros métodos a través de Stripe. Las facturas están disponibles en «Mi cuenta» → «Gestionar pago y facturas».</li>
        <li><strong>Ofertas:</strong> si contratas con un descuento, se aplica durante el tiempo indicado en la oferta; después pagas el precio normal del plan.</li>
        <li><strong>Cambios de precio:</strong> si subimos el precio de un plan, los clientes que ya lo tienen conservan el suyo. Si alguna vez hubiera que cambiarlo, te avisaríamos con al menos 30 días y podrías cancelar sin coste antes de que se aplique.</li>
        <li><strong>Nombres cortos:</strong> las direcciones de pocos caracteres tienen un sobrecoste periódico que se suma al plan mientras las tengas.</li>
    </ul>

    <h2>5. Cambiar de plan y cancelar</h2>
    <ul>
        <li>Desde «Mi cuenta» → «Cambiar de plan». Si subes de plan, el cambio es inmediato y pagas en ese momento la parte proporcional hasta la renovación. Si bajas, también es inmediato y la diferencia se descuenta de tus próximos pagos.</li>
        <li>Puedes <strong>cancelar cuando quieras</strong>, sin permanencia: el plan sigue activo hasta el final del periodo ya pagado. Después, el buzón pasa al plan gratis si cumple sus condiciones (no tienes ya un buzón gratis, cabe en su espacio y no es un nombre corto). Si no las cumple, se trata como un impago (punto 7), con tiempo para descargar tu correo.</li>
    </ul>

    <h2>6. Derecho de desistimiento</h2>
    <p>Si eres consumidor, puedes desistir del contrato de un plan de pago en los <strong>14 días naturales</strong> siguientes a contratarlo, sin dar explicaciones. Al contratar nos pides que el servicio empiece a funcionar de inmediato; por eso, si desistes, te devolvemos lo pagado descontando la parte proporcional al tiempo que hayas tenido el servicio.</p>
    <p>Para desistir, escríbenos a <a href="mailto:{{ $l['email'] }}">{{ $l['email'] }}</a> desde tu email de recuperación indicando tu dirección @{{ $l['brand'] }}; si lo prefieres, puedes usar este modelo: «Por la presente le comunico que desisto de mi contrato del plan [plan] del buzón [dirección], contratado el [fecha]. Nombre, fecha». Te devolveremos el importe en un máximo de 14 días por el mismo medio de pago.</p>

    <h2>7. Impagos</h2>
    <p>Si un cobro falla, te avisaremos y Stripe lo reintentará durante unos días. Si sigue sin pagarse:</p>
    <ul>
        <li>a los <strong>{{ $u['suspend_after_days'] }} días</strong>, suspendemos el buzón: sigue recibiendo correo, pero no puedes entrar ni enviar;</li>
        <li>una semana antes de borrarlo, te avisamos para que puedas pagar o descargar tu correo;</li>
        <li>a los <strong>{{ $u['delete_after_days'] }} días</strong>, borramos el buzón y todo su contenido;</li>
        <li>la dirección queda bloqueada hasta el día {{ $u['release_after_days'] }}, y después puede registrarla otra persona.</li>
    </ul>
    <p>Si pagas antes del borrado, todo vuelve a funcionar al momento.</p>

    <h2>8. Borrar tu cuenta y llevarte tu correo</h2>
    <p>Puedes descargar una copia de tu correo cuando quieras y borrar tu cuenta desde «Mi cuenta». El borrado es inmediato y definitivo; los planes de pago se cancelan en ese momento, sin más cargos. Cada dirección borrada queda bloqueada 90 días antes de que otra persona pueda registrarla, para que nadie reciba correo dirigido a ti.</p>

    <h2>9. Disponibilidad y copias de seguridad</h2>
    <p>Trabajamos para que el servicio esté disponible siempre, pero puede haber interrupciones por mantenimiento, averías o causas ajenas a nosotros; intentaremos que sean breves y avisar con antelación de las programadas. Hacemos copias de seguridad para recuperar el servicio ante un desastre, no para restaurar correos que borres tú; te recomendamos descargar una copia de tu correo de vez en cuando.</p>

    <h2>10. Suspensión por mal uso</h2>
    <p>Si se incumple la política de uso aceptable podemos limitar, suspender o cerrar la cuenta según la gravedad, como se explica en esa política. Si cerramos una cuenta de pago sin que haya habido un mal uso por tu parte, te devolveremos la parte no consumida.</p>

    <h2>11. Responsabilidad</h2>
    <p>Respondemos de los daños que te causemos por incumplir estas condiciones conforme a la ley. No respondemos de los daños causados por un uso del servicio contrario a estas condiciones, por terceros que accedan con tus contraseñas, ni por causas de fuerza mayor. Nada de lo anterior limita los derechos que te reconoce la normativa de consumidores.</p>

    <h2>12. Cambios en estas condiciones</h2>
    <p>Si las cambiamos de forma importante, te avisaremos por email con al menos 30 días de antelación. Si no estás de acuerdo, puedes cancelar o borrar tu cuenta antes de que se apliquen.</p>

    <h2>13. Ley y tribunales</h2>
    <p>Estas condiciones se rigen por la ley española. Si eres consumidor, serán competentes los juzgados de tu domicilio. Antes de nada, escríbenos a <a href="mailto:{{ $l['email'] }}">{{ $l['email'] }}</a>: intentaremos resolverlo contigo.</p>
</x-legal-page>
