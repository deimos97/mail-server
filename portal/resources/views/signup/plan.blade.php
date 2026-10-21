@php
    use App\Support\Money;
    use App\Support\PlanGroups;
    // Un nombre corto (con sobrecoste) necesita plan de pago y solo hay un buzón gratis por usuario (D-014).
    // Los de pago, cuando Stripe está configurado y el plan (y el sobrecoste, si toca) están sincronizados.
    $surchargePrice = fn ($plan) => $plan->interval === 'year' ? $surcharge?->stripe_price_year_id : $surcharge?->stripe_price_id;
    $selectable = fn ($plan) => $plan->is_free
        ? ! $surcharge && ! $hasFree
        : $paidReady && $plan->stripe_price_id && (! $surcharge || $surchargePrice($plan));
    $paidSlugs = $plans->reject->is_free->pluck('slug')->values();
    // Tras un error (p. ej. sin marcar la casilla), el plan que mandó; si no, el que eligió en la landing o al volver de Stripe
    $selected = old('plan', $selected);
    $default = $plans->first(fn ($p) => $p->slug === $selected && $selectable($p)) ?? $plans->first($selectable);
    // Una tarjeta por plan con su selector Anual / Mensual, que arranca en el más barato (o en el elegido);
    // el precio, siempre al mes (App\Support\PlanGroups)
    $groups = PlanGroups::of($plans);
    $period = $groups->mapWithKeys(fn ($g) => [$g['key'] => ($g['plans']->contains($default) ? $default : $g['default'])->interval === 'year' ? 'year' : 'month']);
    $slugs = $groups->mapWithKeys(fn ($g) => [$g['key'] => $g['plans']->mapWithKeys(fn ($p) => [$p->interval === 'year' ? 'year' : 'month' => $p->slug])]);
@endphp

<x-signup-layout :step="$extra ? null : 3" title="Elige tu plan">
    <p class="mt-2 text-stone-600">Para <strong data-ph-mask>{{ $reservation->email() }}</strong>. Precios con IVA incluido.</p>

    @if ($hasFree && $plans->contains('is_free', true))
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Tu cuenta ya tiene su buzón gratis.</p>
            <p class="mt-1 text-stone-700">Cada cuenta incluye un buzón gratis; los demás van con un plan de pago.</p>
            <a href="{{ route('account') }}" class="mt-3 inline-block rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Volver a mi cuenta</a>
        </div>
    @elseif ($surcharge && $plans->contains('is_free', true))
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Los nombres cortos necesitan un plan de pago (+{{ Money::format($surcharge->price_cents) }}/mes).</p>
            <p class="mt-1 text-stone-700">Elige un plan de pago o, si prefieres el gratis, un nombre de {{ $surcharge->max_length + 1 }} caracteres o más.</p>
            <form method="POST" action="{{ route('signup.change-name') }}" class="mt-3">@csrf
                <button class="rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Elegir otro nombre</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('signup.plan.store') }}" class="mt-6"
          x-data="{ plan: @js($default?->slug), paid: @js($paidSlugs), period: @js($period), slugs: @js($slugs),
                    setPeriod(group, yearly) { const was = this.plan === this.slugs[group][this.period[group]]; this.period[group] = yearly ? 'year' : 'month';
                                               if (was && this.slugs[group][this.period[group]]) this.plan = this.slugs[group][this.period[group]] } }">
        @csrf
        <fieldset class="space-y-3">
            <legend class="sr-only">Plan</legend>
            @foreach ($groups as $group)
            @foreach ($group['plans'] as $plan)
                @php $enabled = $selectable($plan); $yearly = $plan->interval === 'year'; $twinned = $group['plans']->count() > 1; @endphp
                <label @if ($twinned) x-show="period[@js($group['key'])] === @js($yearly ? 'year' : 'month')" @if ($period[$group['key']] !== ($yearly ? 'year' : 'month')) x-cloak @endif @endif @class([
                    'flex items-start gap-4 rounded-2xl p-4 ring-2 transition',
                    'cursor-pointer hover:bg-stone-50' => $enabled,
                    'cursor-not-allowed opacity-50' => ! $enabled,
                ]) :class="plan === @js($plan->slug) ? 'ring-rojo bg-rojo/5' : 'ring-stone-200'">
                    <input type="radio" name="plan" value="{{ $plan->slug }}" x-model="plan" @disabled(! $enabled) class="mt-1 size-5 accent-rojo">
                    <span class="flex-1">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="text-lg font-bold">{{ $group['name'] }}</span>
                            <span class="text-right font-bold">{{ $plan->is_free ? 'Gratis' : Money::format(PlanGroups::perMonth($plan, $plan->effectivePriceCents())).'/mes' }}</span>
                        </span>
                        @if ($yearly && ! $plan->is_free)
                            <span class="block text-sm text-stone-500">Pago anual de {{ Money::format($plan->effectivePriceCents()) }}.</span>
                        @endif
                        @if ($plan->features)
                            <span class="mt-1 block text-sm text-stone-600">{{ implode(' · ', $plan->features) }}</span>
                        @endif
                        @if (! $plan->is_free && $surcharge)
                            <span class="mt-1 block text-sm font-semibold text-stone-700">+{{ Money::format($surcharge->price_cents) }}/mes por nombre corto{{ $yearly ? ' ('.Money::format($surcharge->price_cents * 12).' al año)' : '' }}</span>
                        @endif
                        @if ($twinned)
                            <x-billing-toggle :group="$group['key']" class="mt-3" />
                        @endif
                        @if (! $plan->is_free && ! $enabled)
                            <span class="mt-2 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Muy pronto</span>
                        @endif
                        @if ($plan->is_free && $hasFree)
                            <span class="mt-2 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Ya tienes tu buzón gratis</span>
                        @endif
                    </span>
                </label>
            @endforeach
            @endforeach
        </fieldset>
        <x-form-error name="plan" />

        @if ($default)
            <div x-show="paid.includes(plan)" x-cloak class="mt-5 rounded-2xl bg-stone-50 p-4 text-sm">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="immediate_start" value="1" class="mt-0.5 size-5 accent-rojo" @checked(old('immediate_start'))>
                    <span>Quiero que mi correo empiece a funcionar en cuanto pague. Sé que puedo desistir en los 14 días siguientes y que, si lo hago, solo pagaré la parte usada.</span>
                </label>
                <x-form-error name="immediate_start" />
                <p class="mt-3 text-stone-500">El pago se hace en Stripe; nosotros nunca vemos tu tarjeta. Precios con IVA incluido. Puedes cancelar cuando quieras desde tu cuenta.</p>
            </div>
            <button type="submit" class="mt-6 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro"
                    x-text="paid.includes(plan) ? 'Ir al pago' : 'Crear mi correo'">
                Crear mi correo
            </button>
        @endif
    </form>
</x-signup-layout>
