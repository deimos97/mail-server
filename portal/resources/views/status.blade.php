<x-layouts.site title="Estado del servicio · Una Grande y Libre">
    <meta http-equiv="refresh" content="120">
    <main class="mx-auto max-w-2xl px-4 py-12 sm:py-16">
        <a href="{{ route('home') }}" class="text-sm font-medium text-stone-500 underline">← Volver al inicio</a>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">Estado del servicio</h1>

        @if ($stale)
            <div class="mt-6 rounded-3xl bg-stone-100 p-5">
                <p class="text-lg font-bold">Sin datos recientes</p>
                <p class="mt-1 text-stone-600">No hemos recibido la última comprobación automática. Si notas algún problema, escríbenos a <a href="mailto:{{ config('legal.email') }}" class="font-medium underline">{{ config('legal.email') }}</a>.</p>
            </div>
        @elseif ($allOk)
            <div class="mt-6 flex items-center gap-4 rounded-3xl bg-green-50 p-5 ring-1 ring-green-200">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-green-600 text-white" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                </span>
                <p class="text-lg font-bold text-green-900">Todo funciona con normalidad</p>
            </div>
        @else
            <div class="mt-6 rounded-3xl bg-amarillo/20 p-5 ring-1 ring-amarillo/60">
                <p class="text-lg font-bold">Hay una incidencia en curso</p>
                <p class="mt-1 text-stone-700">Ya lo sabemos y estamos trabajando en ello. Tu correo no se pierde: si el envío se retrasa, se entrega en cuanto se resuelva.</p>
            </div>
        @endif

        <ul class="mt-6 divide-y divide-stone-200 rounded-3xl bg-white ring-1 ring-stone-200">
            @foreach ($components as $component)
                <li class="flex items-center justify-between gap-3 px-5 py-4">
                    <span class="font-semibold">{{ $component['label'] }}</span>
                    @if ($stale)
                        <span class="text-sm text-stone-500">Sin datos</span>
                    @elseif ($component['ok'])
                        <span class="flex items-center gap-2 text-sm font-semibold text-green-700"><span class="size-2.5 rounded-full bg-green-600" aria-hidden="true"></span>Operativo</span>
                    @else
                        <span class="flex items-center gap-2 text-sm font-semibold text-rojo"><span class="size-2.5 rounded-full bg-rojo" aria-hidden="true"></span>Con incidencias</span>
                    @endif
                </li>
            @endforeach
        </ul>

        <p class="mt-4 text-sm text-stone-500">
            @if ($checkedAt)
                Última comprobación: {{ $checkedAt->timezone('Europe/Madrid')->format('d/m/Y H:i') }} (hora de Madrid). Se comprueba cada 10 minutos.
            @endif
        </p>
    </main>
    <x-site-footer />
</x-layouts.site>
