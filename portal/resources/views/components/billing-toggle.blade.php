{{-- Anual / Mensual de un plan (en su tarjeta). Va dentro de un x-data con `period` ({grupo: 'year'|'month'}) y `setPeriod(grupo, anual)`. --}}
@props(['group', 'savings' => '2 meses gratis'])
<div {{ $attributes->merge(['class' => 'flex']) }}>
    <div role="radiogroup" aria-label="Forma de pago" class="inline-flex rounded-full bg-stone-100 p-1 text-xs font-semibold ring-1 ring-stone-200">
        <button type="button" role="radio" :aria-checked="period[@js($group)] === 'year'" @click.prevent.stop="setPeriod(@js($group), true)"
                :class="period[@js($group)] === 'year' ? 'bg-white text-tinta shadow-sm' : 'text-stone-600 hover:text-tinta'"
                class="rounded-full px-3 py-1.5 transition">Anual <span class="ml-1 rounded-full bg-amarillo/40 px-1.5 py-0.5 text-[0.7rem] text-tinta">{{ $savings }}</span></button>
        <button type="button" role="radio" :aria-checked="period[@js($group)] !== 'year'" @click.prevent.stop="setPeriod(@js($group), false)"
                :class="period[@js($group)] !== 'year' ? 'bg-white text-tinta shadow-sm' : 'text-stone-600 hover:text-tinta'"
                class="rounded-full px-3 py-1.5 transition">Mensual</button>
    </div>
</div>
