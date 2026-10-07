<ol class="list-decimal space-y-3 pl-5 text-stone-700">
    <li>En Outlook, ve a <strong>Agregar cuenta</strong> (en el móvil: tu foto → <strong>+</strong>).</li>
    <li>Escribe tu dirección <strong data-ph-mask>{{ $email ?? 'tunombre@unagrandeylibre.es' }}</strong>. Si te pregunta el tipo de cuenta, elige <strong>IMAP</strong>.</li>
    <li>Pon la contraseña {{ isset($password) ? 'de arriba' : 'de un dispositivo (créala en tu cuenta)' }}.</li>
    <li>Outlook suele encontrar los servidores solo. Si te los pide:
        @include('setup.steps._settings')
    </li>
</ol>
