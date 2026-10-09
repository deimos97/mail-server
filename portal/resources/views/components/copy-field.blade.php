@props(['value', 'label', 'short' => false, 'mask' => false])
{{-- Campo de solo lectura con botón de copiar: nada que seleccionar a mano ni teclear --}}
<div {{ $attributes->class(['flex min-w-0 items-stretch gap-1.5', 'w-auto' => $short]) }} x-data="{ copied: false }">
    <input type="text" readonly value="{{ $value }}" aria-label="{{ $label }}" @if ($mask) data-ph-mask @endif
           @focus="$el.select()"
           @class([
               'min-w-0 rounded-xl border-0 bg-white px-3 py-2 font-mono text-sm text-tinta ring-1 ring-stone-200 focus:ring-2 focus:ring-rojo/60 focus:outline-none',
               'w-20 text-center' => $short,
               'flex-1' => ! $short,
           ])>
    <button type="button" class="shrink-0 rounded-xl bg-white px-3 py-2 text-sm font-semibold ring-1 ring-stone-300 transition hover:bg-stone-50"
            :class="copied && 'bg-green-50 text-green-800 ring-green-300'"
            @click="navigator.clipboard.writeText(@js($value)); copied = true; setTimeout(() => copied = false, 1500)"
            :aria-label="copied ? 'Copiado' : @js('Copiar '.mb_strtolower($label))">
        <span x-text="copied ? '¡Copiado!' : 'Copiar'">Copiar</span>
    </button>
</div>
