@php
    use App\Support\Money;
    $hasSubscription = (bool) $subscription;
    $choices = $options->reject(fn ($o) => $o['current'] || $o['reason']);
    $meta = $options->mapWithKeys(fn ($o) => [$o['plan']->id => ['free' => $o['plan']->is_free]]);
@endphp

<x-signup-layout title="Cambiar de plan">
    <p class="mt-2 text-stone-600">Para <strong data-ph-mask>{{ $mailbox->email }}</strong>. Precios con IVA incluido.</p>

    @if ($pendingFree)
        <div class="mt-5 rounded-2xl bg-amarillo/20 p-4 text-sm">
            <p class="font-semibold">Tu plan pasará a gratis el {{ $subscription->ends_at->timezone('Europe/Madrid')->format('d/m/Y') }}.</p>
            <form method="POST" action="{{ route('account.plan.resume', $mailbox->id) }}" class="mt-3">@csrf
                <button class="rounded-xl bg-white px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-stone-50">Deshacer y seguir con mi plan</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ route('account.plan.store', $mailbox->id) }}" class="mt-6"
          x-data="{ plan: @js(old('plan', $choices->first()['plan']->id ?? null)), meta: @js($meta), sub: @js($hasSubscription) }">
        @csrf
        <fieldset class="space-y-3">
            <legend class="sr-only">Plan</legend>
            @foreach ($options as $option)
                @php $plan = $option['plan']; $enabled = ! $option['current'] && ! $option['reason']; $per = $plan->interval === 'year' ? '/año' : '/mes'; @endphp
                <label @class([
                    'flex items-start gap-4 rounded-2xl p-4 ring-2 transition',
                    'cursor-pointer hover:bg-stone-50' => $enabled,
                    'cursor-not-allowed' => ! $enabled,
                    'opacity-50' => ! $enabled && ! $option['current'],
                ]) :class="plan == {{ $plan->id }} ? 'ring-rojo bg-rojo/5' : 'ring-stone-200'">
                    <input type="radio" name="plan" value="{{ $plan->id }}" x-model="plan" @disabled(! $enabled) class="mt-1 size-5 accent-rojo">
                    <span class="flex-1">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="text-lg font-bold">{{ $plan->name }}</span>
                            <span class="font-bold">{{ $plan->is_free ? 'Gratis' : Money::format($plan->effectivePriceCents()).$per }}</span>
                        </span>
                        @if ($plan->features)
                            <span class="mt-1 block text-sm text-stone-600">{{ implode(' · ', $plan->features) }}</span>
                        @endif
                        @if ($option['current'])
                            <span class="mt-2 inline-block rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Tu plan actual</span>
                        @elseif ($option['reason'])
                            <span class="mt-2 block text-sm text-stone-600">{{ $option['reason'] }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </fieldset>
        <x-form-error name="plan" />

        @if ($choices->isNotEmpty())
            <div x-show="plan && ! meta[plan]?.free && ! sub" x-cloak class="mt-5 rounded-2xl bg-stone-50 p-4 text-sm">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="immediate_start" value="1" class="mt-0.5 size-5 accent-rojo">
                    <span>Quiero que el nuevo plan empiece en cuanto pague. Sé que puedo desistir en los 14 días siguientes y que, si lo hago, solo pagaré la parte usada.</span>
                </label>
                <p class="mt-3 text-stone-500">El pago se hace en Stripe; nosotros nunca vemos tu tarjeta.</p>
            </div>
            <p x-show="plan && ! meta[plan]?.free && sub" x-cloak class="mt-5 text-sm text-stone-500">
                El cambio es inmediato. Si subes de plan, cobramos ahora la diferencia hasta tu próxima renovación; si bajas, la diferencia se descuenta de tus próximos pagos.
            </p>
            <p x-show="plan && meta[plan]?.free && sub" x-cloak class="mt-5 text-sm text-stone-500">
                Seguirás con tu plan actual hasta el final del periodo que ya has pagado; después pasarás a gratis. Puedes deshacerlo hasta entonces.
            </p>
            <button type="submit" class="mt-6 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro"
                    x-text="meta[plan]?.free ? 'Pasar a gratis' : (sub ? 'Cambiar de plan' : 'Ir al pago')">Cambiar de plan</button>
        @endif
    </form>

    <x-slot:after>
        <p class="mt-4 text-center text-sm text-stone-500"><a href="{{ route('account') }}" class="font-medium underline">Volver a mi cuenta</a></p>
    </x-slot:after>
</x-signup-layout>
