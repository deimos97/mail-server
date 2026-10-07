<ol class="list-decimal space-y-3 pl-5 text-stone-700">
    <li>En Thunderbird: <strong>Cuentas → Nueva cuenta → Correo</strong>.</li>
    <li>Escribe tu nombre, tu dirección <strong data-ph-mask>{{ $email ?? 'tunombre@unagrandeylibre.es' }}</strong> y la contraseña {{ isset($password) ? 'de arriba' : 'de un dispositivo (créala en tu cuenta)' }}.</li>
    <li>Pulsa <strong>Continuar</strong>: Thunderbird encuentra la configuración solo. Pulsa <strong>Hecho</strong>.</li>
</ol>
<p class="mt-4 text-sm text-stone-500">Si prefieres ponerla a mano:</p>
@include('setup.steps._settings')
