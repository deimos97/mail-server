<x-signup-layout :step="1" title="Elige tu dirección">
    <p class="mt-2 text-stone-600">Es la dirección que usarás para siempre. Puedes usar letras, números y . _ -</p>

    <form method="POST" action="{{ route('signup.name') }}" class="mt-6"
          x-data="nameField(@js($domains), { native: true, initial: @js(old('nombre', $value ?? '')) })" @submit.prevent="submit()" novalidate>
        @csrf
        <div class="flex items-center rounded-2xl bg-stone-100 ring-2 ring-transparent transition focus-within:bg-white focus-within:ring-rojo/60">
            <label for="nombre" class="sr-only">Nombre</label>
            <input id="nombre" name="nombre" x-ref="input" x-model="local" type="text" autofocus
                   placeholder="tunombre" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" maxlength="64"
                   class="min-w-0 flex-1 bg-transparent py-4 pl-4 pr-1 text-right text-lg font-semibold outline-none placeholder:font-normal placeholder:text-stone-400"
                   aria-describedby="nombre-estado nombre-error">
            @if (count($domains) > 1)
                <select name="dominio" x-model="domain" class="mr-2 rounded-xl bg-white py-2 text-base font-semibold text-stone-600">
                    @foreach ($domains as $domain)<option value="{{ $domain }}">{{ '@'.$domain }}</option>@endforeach
                </select>
            @else
                <input type="hidden" name="dominio" value="{{ $domains[0] ?? '' }}">
                <span class="whitespace-nowrap pr-4 text-lg font-semibold text-stone-500">{{ '@'.($domains[0] ?? '') }}</span>
            @endif
        </div>

        <div id="nombre-estado" aria-live="polite" data-ph-mask class="mt-3 min-h-6 text-sm">
            <p x-cloak x-show="state === 'checking'" class="text-stone-500">Comprobando…</p>
            <p x-cloak x-show="state === 'limited'" class="text-rojo">Demasiadas comprobaciones seguidas. Espera un minuto.</p>
            <template x-if="state === 'done' && result">
                <div>
                    <p x-show="result.available" class="font-medium text-green-700">
                        <span x-text="result.email"></span> está libre
                        <span x-show="result.requires_paid_plan" class="block font-normal text-stone-600"
                              x-text="'Nombre corto: +' + euros(result.surcharge_cents) + '/mes, con un plan de pago'"></span>
                    </p>
                    <p x-show="!result.available" class="font-medium text-rojo" x-text="result.message"></p>
                    <div x-show="result.suggestions.length" class="mt-2 flex flex-wrap gap-2">
                        <template x-for="s in result.suggestions" :key="s">
                            <button type="button" @click="pick(s)" x-text="s" class="rounded-full bg-stone-100 px-3 py-1 font-semibold hover:bg-stone-200"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
        <x-form-error name="nombre" />

        <button type="submit" class="mt-6 w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro">
            Continuar
        </button>
    </form>
</x-signup-layout>
