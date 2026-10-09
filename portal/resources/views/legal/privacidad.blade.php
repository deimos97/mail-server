<x-legal-page :title="$title">
    <p>Aquí te explicamos qué datos tratamos cuando usas {{ $l['brand'] }}, para qué, durante cuánto tiempo y qué derechos tienes. Lo hacemos conforme al Reglamento General de Protección de Datos (RGPD) y a la Ley Orgánica 3/2018 (LOPDGDD).</p>

    <h2>1. Responsable del tratamiento</h2>
    <p>{{ $l['company'] }} (NIF {{ $l['nif'] }}), {{ $l['address'] }}. Para cualquier cuestión sobre tus datos: <a href="mailto:{{ $l['email'] }}">{{ $l['email'] }}</a>.</p>

    <h2>2. Qué datos tratamos y para qué</h2>
    <table>
        <thead><tr><th>Datos</th><th>Para qué</th><th>Base legal</th></tr></thead>
        <tbody>
            <tr><td>Email de recuperación y contraseña de la web (guardada cifrada, nunca en claro)</td><td>Crear y gestionar tu cuenta, entrar en la web, recuperar el acceso y enviarte avisos del servicio</td><td>Contrato</td></tr>
            <tr><td>Tu dirección de correo, los nombres de tus dispositivos y sus contraseñas (cifradas)</td><td>Prestar el servicio de correo</td><td>Contrato</td></tr>
            <tr><td>El contenido de tu buzón (correos, carpetas, adjuntos)</td><td>Guardarlo, entregártelo y enviarlo en tu nombre. No lo leemos ni lo usamos para nada más (ver punto 5)</td><td>Contrato</td></tr>
            <tr><td>Datos técnicos: IP, fecha y hora de conexiones y envíos, último acceso, espacio usado</td><td>Seguridad, prevención de abusos y spam, diagnóstico de problemas y mostrarte el uso de tu cuenta</td><td>Interés legítimo en la seguridad del servicio</td></tr>
            <tr><td>Datos de pago y facturación (planes de pago)</td><td>Cobrar, emitir facturas y cumplir obligaciones fiscales. Los datos de tu tarjeta los trata Stripe; nosotros nunca los vemos</td><td>Contrato y obligación legal</td></tr>
            <tr><td>Origen de tu visita (campaña, enlace o web de procedencia)</td><td>Saber qué campañas funcionan</td><td>Interés legítimo; con datos de terceros, solo con tu consentimiento</td></tr>
            <tr><td>Uso de la web (páginas, clics, mapas de calor, grabaciones de sesión con los campos ocultos)</td><td>Mejorar la web</td><td>Consentimiento (solo si aceptas las cookies de análisis)</td></tr>
            <tr><td>Verificación anti-robots (Cloudflare Turnstile)</td><td>Evitar altas automáticas</td><td>Interés legítimo en la seguridad</td></tr>
        </tbody>
    </table>
    <p>Para crear una cuenta, los datos marcados como obligatorios son necesarios: sin ellos no podemos prestarte el servicio. No tomamos decisiones automatizadas que te afecten de forma significativa ni elaboramos perfiles con fines comerciales.</p>

    <h2>3. Cuánto tiempo los guardamos</h2>
    <ul>
        <li><strong>Cuenta y buzón:</strong> mientras tengas la cuenta. Si la borras, borramos al momento tu correo y tus datos de la web. La dirección se queda bloqueada 90 días (sin contenido) para que nadie pueda recibir correo dirigido a ti, y después queda libre.</li>
        <li><strong>Copias de seguridad:</strong> guardamos copias cifradas del servidor durante un máximo de un mes. Lo que borres desaparece de ellas en ese plazo; mientras tanto no se usan para nada salvo para recuperar el servicio ante un desastre.</li>
        <li><strong>Registros técnicos:</strong> unas cinco semanas como máximo.</li>
        <li><strong>Cuentas gratuitas sin uso:</strong> si pasan 6 meses sin que entres, te avisamos y, si no vuelves en 30 días, borramos la cuenta.</li>
        <li><strong>Facturas y datos de facturación:</strong> el plazo que exige la ley (en general, 6 años).</li>
        <li><strong>Datos de análisis:</strong> como máximo un año.</li>
    </ul>

    <h2>4. Con quién los compartimos</h2>
    <p>No vendemos tus datos ni los cedemos a terceros, salvo obligación legal. Trabajamos con estos proveedores, que los tratan por cuenta nuestra y con las garantías del RGPD:</p>
    <ul>
        <li><strong>Hetzner Online GmbH</strong> (Alemania): servidores donde se guardan la web, tu correo y las copias de seguridad, en la Unión Europea.</li>
        <li><strong>Stripe Payments Europe, Ltd.</strong> (Irlanda): cobros de los planes de pago. Stripe trata los datos de tu tarjeta y puede actuar también como responsable para cumplir sus obligaciones financieras.</li>
        <li><strong>PostHog</strong> (servidores en la Unión Europea): análisis de uso de la web, solo si aceptas las cookies de análisis.</li>
        <li><strong>Cloudflare, Inc.</strong>: DNS del dominio y verificación anti-robots en el alta.</li>
        <li>Nuestra <strong>asesoría fiscal y contable</strong>, para la contabilidad y los impuestos.</li>
    </ul>
    <p>Algunos de estos proveedores pertenecen a grupos con sede en Estados Unidos. Cuando hay transferencias internacionales de datos, se hacen con las garantías del RGPD (Marco de Privacidad de Datos UE-EE. UU. o cláusulas contractuales tipo de la Comisión Europea).</p>

    <h2>5. Tu correo es tuyo</h2>
    <p>No leemos tu correo ni lo usamos para publicidad. Los sistemas antispam y antivirus lo analizan de forma automática para protegerte. Solo accederíamos a un buzón si tú nos lo pides para resolver una incidencia, o si lo exige una autoridad judicial conforme a la ley.</p>

    <h2>6. Tus derechos</h2>
    <p>Puedes pedirnos en cualquier momento <strong>acceder</strong> a tus datos, <strong>rectificarlos</strong>, <strong>suprimirlos</strong>, <strong>limitar</strong> u <strong>oponerte</strong> a su tratamiento y su <strong>portabilidad</strong>, y retirar tu consentimiento cuando lo hayas dado (sin que afecte a lo anterior). Escríbenos a <a href="mailto:{{ $l['email'] }}">{{ $l['email'] }}</a> desde tu email de recuperación.</p>
    <p>Muchas cosas puedes hacerlas tú mismo desde «Mi cuenta»: cambiar tu email de recuperación, descargar una copia de tu correo o borrar tu cuenta. Las cookies de análisis las puedes aceptar o rechazar en cualquier momento desde «Configurar cookies», en el pie de página.</p>
    <p>Si crees que no hemos tratado bien tus datos, puedes reclamar ante la <a href="https://www.aepd.es" rel="noopener">Agencia Española de Protección de Datos</a>.</p>

    <h2>7. Seguridad</h2>
    <p>Usamos conexiones cifradas en todo el servicio, guardamos las contraseñas con algoritmos de hash, damos a cada dispositivo su propia contraseña que puedes revocar, y limitamos el acceso a los servidores al personal imprescindible.</p>

    <h2>8. Menores</h2>
    <p>Para crear una cuenta debes tener al menos 14 años. Para contratar un plan de pago, ser mayor de edad.</p>

    <h2>9. Cambios</h2>
    <p>Si cambiamos esta política de forma importante, te avisaremos por email antes de que el cambio se aplique.</p>
</x-legal-page>
