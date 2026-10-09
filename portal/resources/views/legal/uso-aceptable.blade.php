<x-legal-page :title="$title">
    <p>Para que tu correo y el de todos lleguen a la bandeja de entrada, necesitamos que el servicio no se use para hacer daño. Esta política forma parte de las <a href="{{ route('legal', 'condiciones') }}">condiciones del servicio</a>.</p>

    <h2>No está permitido</h2>
    <ul>
        <li><strong>Spam:</strong> enviar correo masivo o comercial a quien no lo ha pedido, comprar o alquilar listas de direcciones, o usar el servicio para mandar boletines a gran escala.</li>
        <li><strong>Fraude y suplantación:</strong> phishing, estafas, hacerse pasar por otra persona o entidad, o falsear remitentes.</li>
        <li><strong>Software malicioso:</strong> enviar virus, troyanos o enlaces a ellos.</li>
        <li><strong>Contenido ilegal:</strong> el que infrinja la ley, incluidos el abuso sexual de menores, la incitación al odio o a la violencia, el acoso o la vulneración de derechos de propiedad intelectual de terceros.</li>
        <li><strong>Abusar de los recursos:</strong> superar de forma deliberada los límites de envío, crear cuentas de forma automática o en masa, revender el servicio o intentar acceder a cuentas o sistemas ajenos.</li>
        <li><strong>Saltarse las medidas de seguridad</strong> del servicio o intentar averiguar las contraseñas de otros.</li>
    </ul>

    <h2>Límites de envío</h2>
    <p>Cada plan tiene un número máximo de mensajes por hora, que se indica en su descripción. Sirven para frenar el spam si alguien roba una contraseña. Si un buzón los alcanza, los envíos se retrasan o se rechazan hasta que vuelva a haber margen.</p>

    <h2>Qué hacemos si se incumple</h2>
    <p>Según la gravedad, podemos avisarte, bloquear el envío, suspender el buzón o cerrar la cuenta. En casos graves o ilegales podemos actuar sin aviso previo y colaborar con las autoridades. Si tu cuenta ha sido comprometida (por ejemplo, alguien ha usado una contraseña robada para mandar spam), te ayudaremos a recuperarla.</p>

    <h2>Denunciar un abuso</h2>
    <p>Si recibes spam o un correo fraudulento desde una dirección @{{ $l['brand'] }}, escríbenos a <a href="mailto:{{ $l['abuse'] }}">{{ $l['abuse'] }}</a> con el mensaje completo (con sus cabeceras).</p>
</x-legal-page>
