@php
    use App\Support\Money;
    // Fase 2: solo planes gratis. Un nombre corto (con sobrecoste) necesita plan de pago,
    // y solo hay un buzón gratis por usuario (D-014).
    $selectable = fn ($plan) => $plan->is_free && ! $surcharge && ! $hasFree;
    $default = $plans->first(fn ($p) => $p->slug === $selected && $selectable($p)) ?? $plans->first($selectable);
@endphp

<x-signup-layout :step="$extra ? null : 3" title="Elige tu plan">
    <p class="mt-2 text-stone-600">Para <strong data-ph-mask>{{ $reservation->email() }}</strong>. Precios con IVA incluido.</p>

    @if ($hasFree)
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Tu cuenta ya tiene su buzón gratis.</p>
            <p class="mt-1 text-stone-700">Cada cuenta incluye un buzón gratis; los demás van con un plan de pago. Los planes de pago abren muy pronto.</p>
            <a href="{{ route('account') }}" class="mt-3 inline-block rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Volver a mi cuenta</a>
        </div>
    @elseif ($surcharge)
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Los nombres cortos necesitan un plan de pago (+{{ Money::format($surcharge->price_cents) }}/mes).</p>
            <p class="mt-1 text-stone-700">Los planes de pago abren muy pronto. Mientras, puedes elegir un nombre de 5 caracteres o más.</p>
            <form method="POST" action="{{ route('signup.change-name') }}" class="mt-3">@csrf
                <button class="rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Elegir otro nombre</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('signup.plan.store') }}" class="mt-6" x-data="{ plan: @js($default?->slug) }">
        @csrf
        <fieldset class="space-y-3">
            <legend class="sr-only">Plan</legend>
            @foreach ($plans as $plan)
                @php $enabled = $selectable($plan); $per = $plan->interval === 'year' ? '/año' : '/mes'; @endphp
                <label @class([
                    'flex items-start gap-4 rounded-2xl p-4 ring-2 transition',
                    'cursor-pointer hover:bg-stone-50' => $enabled,
                    'cursor-not-allowed opacity-50' => ! $enabled,
                ]) :class="plan === @js($plan->slug) ? 'ring-rojo bg-rojo/5' : 'ring-stone-200'">
                    <input type="radio" name="plan" value="{{ $plan->slug }}" x-model="plan" @disabled(! $enabled) class="mt-1 size-5 accent-rojo">
                    <span class="flex-1">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="text-lg font-bold">{{ $plan->name }}</span>
                            <span class="font-bold">{{ $plan->is_free ? 'Gratis' : Money::format($plan->effectivePriceCents()).$per }}</span>
                        </span>
                        @if ($plan->features)
                            <span class="mt-1 block text-sm text-stone-600">{{ implode(' · ', $plan->features) }}</span>
                        @endif
                        @unless ($plan->is_free)
                            <span class="mt-2 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Muy pronto</span>
                        @endunless
                        @if ($plan->is_free && $hasFree)
                            <span class="mt-2 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Ya tienes tu buzón gratis</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </fieldset>
        <x-form-error name="plan" />

        @if ($default)
            <button type="submit" class="mt-6 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">
                Crear mi correo
            </button>
        @endif
    </form>
</x-signup-layout>
