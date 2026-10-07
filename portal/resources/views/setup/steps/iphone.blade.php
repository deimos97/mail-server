@isset($profileUrl)
    <a href="{{ $profileUrl }}" class="block w-full rounded-2xl bg-rojo px-6 py-4 text-center text-lg font-bold text-white transition hover:bg-rojo-oscuro">Instalar el perfil</a>
    <p class="mt-2 text-sm text-stone-500">Ábrelo <strong>en el propio iPhone o iPad</strong>, con Safari. El botón vale una vez y caduca en 10 minutos.</p>
@endisset
<ol class="mt-5 list-decimal space-y-3 pl-5 text-stone-700">
    @isset($profileUrl)
        <li>Pulsa <strong>Instalar el perfil</strong> y, cuando Safari lo pregunte, <strong>Permitir</strong>.</li>
    @else
        <li>Entra en <a href="{{ route('login') }}" class="font-semibold text-rojo underline">tu cuenta</a> desde el iPhone, pulsa <strong>Configura un dispositivo → iPhone o iPad</strong> e <strong>Instalar el perfil</strong>.</li>
    @endisset
    <li>Abre <strong>Ajustes</strong>. Arriba verás <strong>Perfil descargado</strong> (si no, en General → VPN y gestión de dispositivos).</li>
    <li>Pulsa <strong>Instalar</strong>, escribe el código del iPhone y vuelve a pulsar <strong>Instalar</strong>. Verás «No verificado»: es normal.</li>
    <li>Abre la app <strong>Mail</strong>: tu cuenta ya está ahí.</li>
</ol>
