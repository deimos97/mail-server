@php
    use App\Support\Money;
    use App\Support\PlanGroups;
    $hasSubscription = (bool) $subscription;
    $choices = $options->reject(fn ($o) => $o['current'] || $o['reason']);
    $current = $options->firstWhere('current', true)['plan']->id ?? null;
    // Una tarjeta por plan con su selector Anual / Mensual (en el del plan actual, o en el más barato);
    // el precio, siempre al mes (App\Support\PlanGroups)
    $byId = $options->keyBy(fn ($o) => $o['plan']->id);
    $groups = PlanGroups::of($options->pluck('plan'));
    $currentPlan = $options->firstWhere('current', true)['plan'] ?? null;
    $period = $groups->mapWithKeys(fn ($g) => [$g['key'] => ($currentPlan && $g['plans']->contains($currentPlan) ? $currentPlan : $g['default'])->interval === 'year' ? 'year' : 'month']);
    $ids = $groups->mapWithKeys(fn ($g) => [$g['key'] => $g['plans']->mapWithKeys(fn ($p) => [$p->interval === 'year' ? 'year' : 'month' => $p->id])]);
    $meta = $options->mapWithKeys(fn ($o) => [$o['plan']->id => ['free' => $o['plan']->is_free]]);
@endphp

<x-signup-layout title="Cambiar de plan">
    <p class="mt-2 text-stone-600">Para <strong data-ph-mask>{{ $mailbox->email }}</strong>. Precios con IVA incluido.</p>

    @php $fmt = fn ($date) => $date->timezone('Europe/Madrid')->format('d/m/Y'); @endphp
    @if ($pendingFree)
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            @if ($fitsFree)
                <p class="font-semibold">Tu plan pasará a gratis el {{ $fmt($subscription->ends_at) }}.</p>
                @if ($aliasCount)
                    <p class="mt-1 text-stone-700">Ese día {{ $aliasCount === 1 ? 'tu alias dejará' : "tus {$aliasCount} alias dejarán" }} de funcionar: el plan gratis no tiene alias.</p>
                @endif
            @else
                <p class="font-semibold">Has cancelado el plan: perderás este buzón.</p>
                <p class="mt-1 text-stone-700">
                    Funciona hasta el {{ $fmt($subscription->ends_at) }}; después se suspende{{ $loss ? ' y el '.$fmt($loss['delete']).' borramos todo su correo' : '' }}.
                </p>
            @endif
            <form method="POST" action="{{ route('account.plan.resume', $mailbox->id) }}" class="mt-3">@csrf
                <button class="rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Deshacer y seguir con mi plan</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('account.plan.store', $mailbox->id) }}" class="mt-6"
          x-data="{ plan: @js(old('plan', $current ?? $choices->first()['plan']->id ?? null)), current: @js($current), meta: @js($meta), sub: @js($hasSubscription),
                    period: @js($period), ids: @js($ids),
                    setPeriod(group, yearly) { const was = this.plan == this.ids[group][this.period[group]]; this.period[group] = yearly ? 'year' : 'month';
                                               if (was && this.ids[group][this.period[group]]) this.plan = this.ids[group][this.period[group]] } }">
        @csrf
        <fieldset class="space-y-3">
            <legend class="sr-only">Plan</legend>
            @foreach ($groups as $group)
            @foreach ($group['plans'] as $plan)
                @php
                    $option = $byId[$plan->id]; $enabled = ! $option['current'] && ! $option['reason'];
                    $yearly = $plan->interval === 'year'; $twinned = $group['plans']->count() > 1;
                @endphp
                <label @if ($twinned) x-show="period[@js($group['key'])] === @js($yearly ? 'year' : 'month')" @if ($period[$group['key']] !== ($yearly ? 'year' : 'month')) x-cloak @endif @endif @class([
                    'flex items-start gap-4 rounded-2xl p-4 ring-2 transition',
                    'cursor-pointer hover:bg-stone-50' => $enabled,
                    'cursor-not-allowed' => ! $enabled,
                    'opacity-50' => ! $enabled && ! $option['current'],
                ]) :class="plan == {{ $plan->id }} ? 'ring-rojo bg-rojo/5' : 'ring-stone-200'">
                    <input type="radio" name="plan" value="{{ $plan->id }}" x-model="plan" @disabled(! $enabled) class="mt-1 size-5 accent-rojo">
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
                        @if ($option['current'])
                            <span class="mt-2 inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Tu plan actual</span>
                        @elseif ($option['reason'])
                            <span class="mt-2 block text-sm text-stone-600">{{ $option['reason'] }}</span>
                        @elseif (($lost = $aliasCount - (int) $plan->max_aliases) > 0)
                            <span class="mt-2 block text-sm font-medium text-amber-800">
                                @if ($lost === $aliasCount)
                                    {{ $lost === 1 ? 'Perderás tu alias' : "Perderás tus {$lost} alias" }}: este plan no tiene.
                                @else
                                    Perderás {{ $lost === 1 ? 'tu alias más nuevo' : "tus {$lost} alias más nuevos" }}: este plan tiene {{ $plan->max_aliases }}.
                                @endif
                            </span>
                        @endif
                        @if ($twinned)
                            <x-billing-toggle :group="$group['key']" class="mt-3" />
                        @endif
                    </span>
                </label>
            @endforeach
            @endforeach
        </fieldset>
        <x-form-error name="plan" />

        @if ($choices->isNotEmpty())
            <div x-show="plan != current && ! meta[plan]?.free && ! sub" x-cloak class="mt-5 rounded-2xl bg-stone-50 p-4 text-sm">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="immediate_start" value="1" class="mt-0.5 size-5 accent-rojo">
                    <span>Quiero que el nuevo plan empiece en cuanto pague. Sé que puedo desistir en los 14 días siguientes y que, si lo hago, solo pagaré la parte usada.</span>
                </label>
                <p class="mt-3 text-stone-500">El pago se hace en Stripe; nosotros nunca vemos tu tarjeta.</p>
            </div>
            <p x-show="plan != current && ! meta[plan]?.free && sub" x-cloak class="mt-5 text-sm text-stone-500">
                El cambio es inmediato. Si subes de plan, cobramos ahora la diferencia hasta tu próxima renovación; si bajas, la diferencia se descuenta de tus próximos pagos.
            </p>
            <p x-show="plan != current && meta[plan]?.free && sub" x-cloak class="mt-5 text-sm text-stone-500">
                Seguirás con tu plan actual hasta el final del periodo que ya has pagado; después pasarás a gratis. Puedes deshacerlo hasta entonces.
            </p>
            {{-- De entrada está marcado el plan actual (más claro); el botón se activa al elegir otro --}}
            <button type="submit" :disabled="plan == current" class="mt-6 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro disabled:cursor-not-allowed disabled:opacity-50"
                    x-text="plan == current ? 'Elige otro plan' : (meta[plan]?.free ? 'Pasar a gratis' : (sub ? 'Cambiar de plan' : 'Ir al pago'))">Cambiar de plan</button>
        @endif
    </form>

    {{-- Cancelar: solo para los que no pueden pasar a gratis (los demás eligen "Gratis" arriba). Avisa de que se pierde. --}}
    @if ($subscription && ! $pendingFree && ! $fitsFree)
        @php
            $why = $options->first(fn ($o) => $o['plan']->is_free)['reason'] ?? null;
            $body = $loss
                ? "Seguirá funcionando hasta el {$fmt($loss['end'])}. Después lo suspenderemos y el {$fmt($loss['delete'])} borraremos todo su correo; más adelante, el nombre quedará libre para otras personas. Puedes deshacerlo hasta el {$fmt($loss['end'])}."
                : 'Seguirá funcionando hasta el final del periodo pagado. Después lo suspenderemos y, pasados unos días, borraremos todo su correo. Puedes deshacerlo hasta entonces.';
        @endphp
        <div class="mt-10 border-t border-stone-200 pt-6 text-sm">
            <h2 class="font-semibold">Cancelar mi plan</h2>
            <p class="mt-1 text-stone-600">
                @if ($why)
                    Este buzón no puede pasar al plan gratis: {{ lcfirst(rtrim($why, '.')) }}.
                @endif
                <strong>Si cancelas, lo perderás</strong> con todo su correo. Si quieres conservarlo, elige otro plan arriba.
            </p>
            <form method="POST" action="{{ route('account.plan.cancel', $mailbox->id) }}" class="mt-3"
                  data-confirm="¿Cancelar el plan y perder {{ $mailbox->email }}?" data-confirm-body="{{ $body }}" data-confirm-button="Cancelar y perder el buzón">
                @csrf
                <button class="font-semibold text-rojo underline hover:text-rojo-oscuro">Cancelar mi plan</button>
            </form>
        </div>
    @endif

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
