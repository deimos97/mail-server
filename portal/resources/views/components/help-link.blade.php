@props(['tone' => 'dark'])
{{-- Botón de ayuda de la cabecera (Fase 7): lleva a /soporte. tone="light" sobre la bandera de la home --}}
<a href="{{ route('support') }}" aria-label="Ayuda y contacto" title="Ayuda y contacto" @class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium transition',
    'text-stone-600 ring-1 ring-stone-300 hover:bg-white hover:text-tinta' => $tone === 'dark',
    'text-white/90 hover:bg-white/10' => $tone === 'light',
])>
    <x-icon.help class="size-5" />
    <span class="hidden sm:inline">Ayuda</span>
</a>
