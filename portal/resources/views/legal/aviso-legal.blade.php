<x-legal-page :title="$title">
    <h2>Titular del sitio web</h2>
    <p>En cumplimiento del artículo 10 de la Ley 34/2002, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI), te informamos de que el sitio web <strong>{{ $l['brand'] }}</strong> y el servicio de correo que se ofrece en él pertenecen a:</p>
    <ul>
        <li><strong>Denominación social:</strong> {{ $l['company'] }}</li>
        <li><strong>NIF:</strong> {{ $l['nif'] }}</li>
        <li><strong>Domicilio:</strong> {{ $l['address'] }}</li>
        <li><strong>Registro:</strong> {{ $l['registry'] }}</li>
        <li><strong>Contacto:</strong> <a href="mailto:{{ $l['email'] }}">{{ $l['email'] }}</a> · {{ $l['phone'] }}</li>
    </ul>
    <p>«{{ $l['brand'] }}» es la marca con la que {{ $l['company'] }} presta este servicio.</p>

    <h2>Uso del sitio</h2>
    <p>El acceso a esta web es libre. Contratar el servicio de correo, gratis o de pago, se rige por las <a href="{{ route('legal', 'condiciones') }}">condiciones del servicio</a> y la <a href="{{ route('legal', 'uso-aceptable') }}">política de uso aceptable</a>. El tratamiento de tus datos se explica en la <a href="{{ route('legal', 'privacidad') }}">política de privacidad</a> y el uso de cookies, en la <a href="{{ route('legal', 'cookies') }}">política de cookies</a>.</p>

    <h2>Contenidos y responsabilidad</h2>
    <p>Procuramos que la información de esta web sea correcta y esté al día, pero, con los límites que establece la ley, no respondemos de errores puntuales que pueda contener. Los contenidos son informativos y no constituyen asesoramiento de ningún tipo.</p>
    <p>La web puede enlazar a páginas de terceros (por ejemplo, guías de aplicaciones de correo o asistentes de inteligencia artificial). No controlamos esas páginas y no respondemos de su contenido.</p>

    <h2>Propiedad intelectual e industrial</h2>
    <p>Los textos, el diseño, el código, los logotipos y el resto de contenidos de esta web son de {{ $l['company'] }} o de sus licenciantes. No se pueden reproducir, distribuir ni comunicar públicamente sin autorización, salvo para uso personal y privado. Las marcas de terceros que aparecen (por ejemplo, las de aplicaciones de correo o asistentes de IA) pertenecen a sus titulares y se usan solo para identificarlas.</p>

    <h2>Ley aplicable</h2>
    <p>Este aviso legal se rige por la ley española.</p>
</x-legal-page>
