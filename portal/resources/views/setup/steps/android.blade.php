<ol class="list-decimal space-y-3 pl-5 text-stone-700">
    <li>Abre <strong>Gmail</strong>, pulsa tu foto (arriba a la derecha) → <strong>Añadir otra cuenta</strong> → <strong>Otra</strong>.</li>
    <li>Escribe tu dirección <strong data-ph-mask>{{ $email ?? 'tunombre@unagrandeylibre.es' }}</strong> y pulsa <strong>Configuración manual</strong> → <strong>Personal (IMAP)</strong>.</li>
    <li>Pon la contraseña {{ isset($password) ? 'de arriba' : 'de un dispositivo (créala en tu cuenta)' }}.</li>
    <li>Si te pide los servidores, usa estos datos (en «Seguridad», <strong>SSL/TLS</strong>):
        @include('setup.steps._settings')
    </li>
    <li>Acepta las opciones de sincronización. Tu correo aparecerá en Gmail.</li>
</ol>
