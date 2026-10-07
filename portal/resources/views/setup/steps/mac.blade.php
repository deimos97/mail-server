@isset($profileUrl)
    <a href="{{ $profileUrl }}" class="block w-full rounded-2xl bg-rojo px-6 py-4 text-center text-lg font-bold text-white transition hover:bg-rojo-oscuro">Descargar el perfil</a>
    <p class="mt-2 text-sm text-stone-500">El botón vale una vez y caduca en 10 minutos.</p>
@endisset
<ol class="mt-5 list-decimal space-y-3 pl-5 text-stone-700">
    @isset($profileUrl)
        <li>Pulsa <strong>Descargar el perfil</strong> y ábrelo (doble clic en el archivo descargado).</li>
    @else
        <li>Entra en <a href="{{ route('login') }}" class="font-semibold text-rojo underline">tu cuenta</a> desde el Mac, pulsa <strong>Configura un dispositivo → Mac</strong> y descarga el perfil.</li>
    @endisset
    <li>Abre <strong>Ajustes del Sistema → General → Gestión de dispositivos</strong> (en versiones anteriores, <strong>Perfiles</strong>).</li>
    <li>Selecciona el perfil del correo y pulsa <strong>Instalar</strong>.</li>
    <li>Abre la app <strong>Mail</strong>: tu cuenta ya está ahí.</li>
</ol>
