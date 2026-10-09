@props(['title'])
@php $l = config('legal'); @endphp
<x-layouts.site :title="$title.' · Una Grande y Libre'">
    <main class="mx-auto max-w-3xl px-4 py-12 sm:py-16">
        <a href="{{ route('home') }}" class="text-sm font-medium text-stone-500 underline">← Volver al inicio</a>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $title }}</h1>
        <p class="mt-2 text-sm text-stone-500">Última actualización: {{ $l['updated'] }}</p>
        @if ($l['draft'])
            <p class="mt-4 rounded-2xl bg-amarillo/20 px-4 py-3 text-sm font-medium">Borrador pendiente de revisión por la asesoría jurídica. Puede cambiar antes del lanzamiento.</p>
        @endif
        <div class="legal mt-8">
            {{ $slot }}
        </div>
        <nav class="mt-12 flex flex-wrap gap-x-4 gap-y-2 border-t border-stone-200 pt-6 text-sm" aria-label="Textos legales">
            @foreach (config('landing.legal') as $slug => $name)
                <a href="{{ route('legal', $slug) }}" class="font-medium text-stone-600 underline hover:text-rojo">{{ $name }}</a>
            @endforeach
        </nav>
    </main>
    <x-site-footer />
</x-layouts.site>
