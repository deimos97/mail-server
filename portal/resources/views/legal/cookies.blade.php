<x-legal-page :title="$title">
    <p>Una cookie es un pequeño archivo que una web guarda en tu navegador. En {{ $l['brand'] }} usamos las imprescindibles para que la web funcione y, <strong>solo si las aceptas</strong>, otras para analizar cómo se usa. No usamos cookies de publicidad.</p>

    <h2>Cookies técnicas (siempre activas)</h2>
    <p>Son necesarias para que puedas entrar en tu cuenta y usar la web con seguridad; no necesitan tu consentimiento.</p>
    <table>
        <thead><tr><th>Cookie</th><th>Para qué</th><th>Duración</th></tr></thead>
        <tbody>
            <tr><td><code>una-grande-y-libre-session</code></td><td>Mantener tu sesión y el alta en curso</td><td>Hasta cerrar sesión o unas horas sin uso</td></tr>
            <tr><td><code>XSRF-TOKEN</code></td><td>Proteger los formularios frente a ataques (CSRF)</td><td>Igual que la sesión</td></tr>
            <tr><td><code>remember_web_…</code></td><td>Mantener la sesión si marcas «Mantener la sesión en este dispositivo»</td><td>Hasta que cierres sesión</td></tr>
            <tr><td><code>ugl_consent</code></td><td>Recordar si aceptaste o rechazaste las cookies de análisis</td><td>12 meses</td></tr>
            <tr><td><code>roundcube_sessid</code>, <code>roundcube_sessauth</code></td><td>Tu sesión en el webmail (webmail.{{ $l['brand'] }})</td><td>Hasta cerrar sesión</td></tr>
        </tbody>
    </table>
    <p>En el alta, la verificación anti-robots de Cloudflare Turnstile puede usar datos técnicos de tu navegador para comprobar que no eres un robot.</p>

    <h2>Cookies de análisis (solo si las aceptas)</h2>
    <p>Nos ayudan a entender qué partes de la web funcionan y cuáles no (páginas vistas, clics, mapas de calor y grabaciones de sesión con todos los campos ocultos). Usamos PostHog, con los datos en la Unión Europea. No se cargan hasta que pulsas «Aceptar».</p>
    <table>
        <thead><tr><th>Cookie</th><th>Para qué</th><th>Duración</th></tr></thead>
        <tbody>
            <tr><td><code>ph_…_posthog</code> (y almacenamiento local del navegador)</td><td>Identificar tu navegador de forma anónima para medir el uso de la web</td><td>12 meses</td></tr>
        </tbody>
    </table>

    <h2>Cómo cambiar de opinión</h2>
    <p>Puedes aceptar o rechazar las cookies de análisis cuando quieras desde el enlace <strong>«Cookies»</strong> del pie de página. Si las rechazas después de haberlas aceptado, dejamos de medir y borramos lo que PostHog guardó en tu navegador. También puedes borrar las cookies desde la configuración de tu navegador.</p>

    <p>Más información sobre cómo tratamos tus datos en la <a href="{{ route('legal', 'privacidad') }}">política de privacidad</a>.</p>
</x-legal-page>
