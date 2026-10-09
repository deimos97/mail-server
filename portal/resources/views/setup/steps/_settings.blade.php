@php $c = config('mail_clients'); @endphp
<div class="mt-3 space-y-4 rounded-2xl bg-stone-100 p-4 text-sm">
    <div>
        <p class="mb-1.5 text-stone-500">Usuario <span class="text-stone-400">(tu dirección completa)</span></p>
        @isset($email)
            <x-copy-field :value="$email" label="Usuario" mask />
        @else
            <p class="font-mono">tunombre@unagrandeylibre.es</p>
        @endisset
    </div>

    <div>
        <p class="mb-1.5 text-stone-500">Contraseña</p>
        @isset($password)
            <div class="flex items-center justify-between gap-3">
                <span>La de arriba (16 letras y números)</span>
                <x-copy-field :value="$password" label="Contraseña" short mask class="[&>input]:hidden" />
            </div>
        @else
            <p>La de un dispositivo (créala en Mi cuenta → Configura un dispositivo)</p>
        @endisset
    </div>

    @foreach (['imap' => 'Servidor de entrada (IMAP)', 'smtp' => 'Servidor de salida (SMTP)'] as $key => $title)
        <div>
            <p class="mb-1.5 text-stone-500">{{ $title }}</p>
            <x-copy-field :value="$c['host']" :label="$title" />
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                <span class="text-stone-500">Puerto</span>
                <x-copy-field :value="(string) $c[$key]['port']" :label="'Puerto '.strtoupper($key)" short />
                <span class="text-stone-500">Seguridad: <strong class="text-tinta">SSL/TLS</strong></span>
            </div>
        </div>
    @endforeach
</div>
<p class="mt-2 text-xs text-stone-500">Si tu app no conecta por el {{ $c['smtp']['port'] }}, usa el {{ $c['smtp_alt']['port'] }} con STARTTLS.</p>
