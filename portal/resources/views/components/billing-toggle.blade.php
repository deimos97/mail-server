{{-- Mensual / Anual (D-029). Va dentro de un x-data con `yearly` y `setYearly(bool)`. --}}
@props(['savings' => '2 meses gratis'])
<div {{ $attributes->merge(['class' => 'flex justify-center']) }}>
    <div role="radiogroup" aria-label="Forma de pago" class="inline-flex rounded-full bg-stone-100 p-1 text-sm font-semibold ring-1 ring-stone-200">
        <button type="button" role="radio" :aria-checked="! yearly" @click="setYearly(false)"
                :class="! yearly ? 'bg-white text-tinta shadow-sm' : 'text-stone-600 hover:text-tinta'"
                class="rounded-full px-4 py-2 transition">Mensual</button>
        <button type="button" role="radio" :aria-checked="yearly" @click="setYearly(true)"
                :class="yearly ? 'bg-white text-tinta shadow-sm' : 'text-stone-600 hover:text-tinta'"
                class="rounded-full px-4 py-2 transition">Anual <span class="ml-1 rounded-full bg-amarillo/40 px-2 py-0.5 text-xs text-tinta">{{ $savings }}</span></button>
    </div>
</div>
