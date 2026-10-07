@php $c = config('mail_clients'); @endphp
<dl class="mt-3 grid gap-x-6 gap-y-3 rounded-2xl bg-stone-100 p-4 text-sm sm:grid-cols-2 [&>div]:min-w-0 [&_dd]:break-all">
    <div><dt class="text-stone-500">Usuario</dt><dd class="font-mono" data-ph-mask>{{ $email ?? 'tu dirección completa (tunombre@unagrandeylibre.es)' }}</dd></div>
    <div><dt class="text-stone-500">Contraseña</dt><dd>{{ isset($password) ? 'la de arriba' : 'la de un dispositivo (créala en Mi cuenta)' }}</dd></div>
    <div><dt class="text-stone-500">Servidor de entrada (IMAP)</dt><dd class="font-mono">{{ $c['host'] }} · {{ $c['imap']['port'] }} · SSL/TLS</dd></div>
    <div><dt class="text-stone-500">Servidor de salida (SMTP)</dt><dd class="font-mono">{{ $c['host'] }} · {{ $c['smtp']['port'] }} · SSL/TLS</dd></div>
</dl>
<p class="mt-2 text-xs text-stone-500">Si tu app no conecta por el {{ $c['smtp']['port'] }}, usa el {{ $c['smtp_alt']['port'] }} con STARTTLS.</p>
