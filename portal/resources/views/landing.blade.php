@php
    use App\Support\Money;
    $hero = config('landing.hero');
    $mainDomain = $domains[0] ?? 'unagrandeylibre.es';
@endphp

<x-layouts.site :structured-data="$structuredData">

    {{-- HERO: la bandera ondeando detrás y el nombre por delante --}}
    <section class="hero-fallback relative isolate flex min-h-[100svh] flex-col overflow-hidden text-white">
        <canvas data-flag class="flag-canvas absolute inset-0 -z-20 size-full" aria-hidden="true"></canvas>
        <div class="hero-veil absolute inset-0 -z-10" aria-hidden="true"></div>

        <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
            <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight">
                unagrandeylibre<span class="text-amarillo">.es</span>
            </a>
            <nav class="flex items-center gap-2">
                <a href="https://webmail.{{ $mainDomain }}" class="hidden rounded-full px-4 py-2 text-sm font-medium text-white/90 transition hover:bg-white/10 sm:inline-block">Webmail</a>
                @auth
                    <a href="{{ route('account') }}" class="rounded-full px-4 py-2 text-sm font-medium text-white/90 ring-1 ring-white/40 transition hover:bg-white/10">Mi cuenta</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-medium text-white/90 ring-1 ring-white/40 transition hover:bg-white/10">Entrar</a>
                @endauth
            </nav>
        </header>

        <div class="hero-text-shadow mx-auto flex w-full max-w-3xl flex-1 flex-col justify-center px-4 pb-24 pt-8 text-center sm:px-6">
            <p class="mx-auto w-fit rounded-full bg-black/30 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-amarillo-claro backdrop-blur-sm sm:text-sm">{{ $hero['eyebrow'] }}</p>
            <h1 class="mt-4 text-4xl font-extrabold leading-[1.05] tracking-tight text-balance sm:text-6xl">
                {{ $hero['title'] }}
            </h1>
            <p class="mx-auto mt-5 max-w-xl text-lg text-white/85 text-pretty">{{ $hero['subtitle'] }}</p>

            <form x-data="nameField(@js($domains))" @submit.prevent="submit()" class="mx-auto mt-8 w-full max-w-2xl" novalidate>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="flex min-w-0 flex-1 items-center rounded-2xl bg-white text-tinta shadow-2xl shadow-black/30 ring-4 ring-transparent transition focus-within:ring-amarillo/70">
                        <label for="nombre" class="sr-only">Nombre de tu dirección de correo</label>
                        <input id="nombre" x-ref="input" x-model="local" type="text" name="nombre"
                               placeholder="tunombre" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false"
                               maxlength="64" enterkeyhint="go"
                               class="min-w-0 flex-1 rounded-l-2xl bg-transparent py-4 pl-5 pr-1 text-right text-lg font-semibold outline-none placeholder:font-normal placeholder:text-stone-400 sm:text-xl"
                               :aria-invalid="result && !result.available"
                               aria-describedby="nombre-estado">
                        @if (count($domains) > 1)
                            <label for="dominio" class="sr-only">Dominio</label>
                            <select id="dominio" x-model="domain" class="mr-2 rounded-xl bg-stone-100 py-2 pl-2 pr-1 text-base font-semibold text-stone-600 outline-none sm:text-lg">
                                @foreach ($domains as $domain)
                                    <option value="{{ $domain }}">{{ '@'.$domain }}</option>
                                @endforeach
                            </select>
                        @else
                            <span class="whitespace-nowrap pr-4 text-base font-semibold text-stone-500 sm:pr-5 sm:text-xl">{{ '@'.$mainDomain }}</span>
                        @endif
                    </div>
                    <button type="submit"
                            class="rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white shadow-2xl shadow-black/30 transition hover:bg-rojo-oscuro focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-amarillo">
                        {{ $hero['cta'] }}
                    </button>
                </div>

                {{-- Estado de la comprobación (lo leen también los lectores de pantalla) --}}
                <div id="nombre-estado" aria-live="polite" data-ph-mask class="mt-4 min-h-[3.5rem] text-base">
                    <p x-cloak x-show="state === 'checking'" class="text-white/80">Comprobando…</p>
                    <p x-cloak x-show="state === 'limited'" class="text-amarillo-claro">Demasiadas comprobaciones seguidas. Espera un minuto.</p>
                    <p x-cloak x-show="state === 'error'" class="text-amarillo-claro">No hemos podido comprobarlo. Inténtalo de nuevo.</p>

                    <template x-if="state === 'done' && result">
                        <div>
                            <p x-show="result.available" class="inline-flex flex-wrap items-center justify-center gap-x-2 gap-y-1 rounded-full bg-white/15 px-4 py-2 font-medium backdrop-blur-sm">
                                <x-icon.check class="size-5 text-amarillo" />
                                <span><strong x-text="result.email"></strong> está libre</span>
                                <span x-show="result.requires_paid_plan" class="w-full text-sm text-amarillo-claro"
                                      x-text="'Nombre corto: +' + euros(result.surcharge_cents) + '/mes, con un plan de pago'"></span>
                            </p>
                            <p x-show="!result.available" class="font-medium text-amarillo-claro" x-text="result.message"></p>
                            <div x-show="result.suggestions.length" class="mt-3 flex flex-wrap items-center justify-center gap-2">
                                <span class="text-sm text-white/75">Prueba con:</span>
                                <template x-for="suggestion in result.suggestions" :key="suggestion">
                                    <button type="button" @click="pick(suggestion)" x-text="suggestion"
                                            class="rounded-full bg-white/90 px-3 py-1 text-sm font-semibold text-tinta transition hover:bg-white"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </form>
        </div>

        <a href="#planes" class="absolute inset-x-0 bottom-6 mx-auto w-fit text-sm font-medium text-white/80 transition hover:text-white">
            Ver planes ↓
        </a>
    </section>

    {{-- PLANES (salen de la BD) --}}
    <section id="planes" class="scroll-mt-4 px-4 py-20 sm:px-6 sm:py-28" x-data>
        <div class="mx-auto max-w-6xl">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ config('landing.plans.title') }}</h2>
                <p class="mt-3 text-lg text-stone-600">{{ config('landing.plans.subtitle') }}</p>
                <p x-cloak x-show="$store.signup.email" data-ph-mask class="mt-6 inline-flex items-center gap-2 rounded-full bg-amarillo/25 px-4 py-2 font-medium">
                    <x-icon.check class="size-5 text-rojo" />
                    Tu dirección: <strong x-text="$store.signup.email"></strong>
                </p>
            </div>

            @if ($plans->isEmpty())
                <p class="mt-12 text-center text-stone-500">Muy pronto publicaremos los planes.</p>
            @else
                <div @class([
                    'mx-auto mt-14 grid gap-6',
                    'max-w-md' => $plans->count() === 1,
                    'max-w-4xl md:grid-cols-2' => $plans->count() === 2,
                    'md:grid-cols-3' => $plans->count() >= 3,
                ])>
                    @foreach ($plans as $plan)
                        @php
                            $per = $plan['interval'] === 'year' ? '/año' : '/mes';
                            $hasOffer = $plan['offer'] !== null;
                        @endphp
                        <article @class([
                            'relative flex flex-col rounded-3xl bg-white p-8 shadow-sm ring-1',
                            'ring-2 ring-rojo shadow-xl shadow-rojo/10 md:-translate-y-2' => $plan['is_highlighted'],
                            'ring-stone-200' => ! $plan['is_highlighted'],
                        ])>
                            @if ($plan['is_highlighted'])
                                <p class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-rojo px-4 py-1 text-xs font-bold uppercase tracking-wider text-white">
                                    El más elegido
                                </p>
                            @endif

                            <h3 class="text-xl font-bold">{{ $plan['name'] }}</h3>
                            @if ($plan['description'])
                                <p class="mt-1 text-stone-600">{{ $plan['description'] }}</p>
                            @endif

                            <div class="mt-6">
                                @if ($plan['is_free'])
                                    <p class="text-4xl font-extrabold tracking-tight">Gratis</p>
                                @else
                                    @if ($hasOffer)
                                        <p class="text-sm text-stone-500">
                                            <del aria-label="Antes">{{ Money::format($plan['price_cents']) }}</del>
                                            <span class="ml-1 rounded-full bg-amarillo/30 px-2 py-0.5 text-xs font-bold text-tinta">{{ $plan['offer']->label }}</span>
                                        </p>
                                    @endif
                                    <p class="text-4xl font-extrabold tracking-tight">
                                        {{ Money::format($plan['effective_price_cents']) }}<span class="text-base font-medium text-stone-500">{{ $per }}</span>
                                    </p>
                                    @if ($hasOffer && $plan['offer']->duration === 'repeating' && $plan['offer']->duration_months)
                                        <p class="mt-1 text-sm text-stone-500">Los primeros {{ $plan['offer']->duration_months }} meses; después, {{ Money::format($plan['price_cents']) }}{{ $per }}.</p>
                                    @elseif ($hasOffer && $plan['offer']->duration === 'once')
                                        <p class="mt-1 text-sm text-stone-500">El primer pago; después, {{ Money::format($plan['price_cents']) }}{{ $per }}.</p>
                                    @endif
                                @endif
                            </div>

                            @if ($plan['features'])
                                <ul class="mt-6 space-y-3 text-stone-700">
                                    @foreach ($plan['features'] as $feature)
                                        <li class="flex gap-3"><x-icon.check class="mt-0.5 size-5 shrink-0 text-rojo" /><span>{{ $feature }}</span></li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-auto pt-8">
                                @if ($plan['is_free'])
                                    <p x-cloak x-show="$store.signup.needsPaidPlan" class="mb-3 text-sm text-stone-500">Los nombres cortos necesitan un plan de pago.</p>
                                @endif
                                <a href="{{ route('signup', ['plan' => $plan['slug']]) }}"
                                   data-plan="{{ $plan['slug'] }}" data-free="{{ $plan['is_free'] ? 1 : 0 }}" data-offer="{{ $hasOffer ? 1 : 0 }}"
                                   :href="'{{ route('signup') }}?' + new URLSearchParams({ plan: @js($plan['slug']), ...($store.signup.email ? { nombre: $store.signup.check.local_part, dominio: $store.signup.check.domain } : {}) })"
                                   @if ($plan['is_free']) :class="$store.signup.needsPaidPlan && 'pointer-events-none opacity-40'" :aria-disabled="$store.signup.needsPaidPlan" @endif
                                   @class([
                                       'block rounded-2xl px-6 py-3.5 text-center font-bold transition focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-amarillo',
                                       'bg-rojo text-white hover:bg-rojo-oscuro' => $plan['is_highlighted'],
                                       'bg-stone-100 text-tinta hover:bg-stone-200' => ! $plan['is_highlighted'],
                                   ])>
                                    {{ $plan['is_free'] ? 'Empezar gratis' : 'Elegir '.$plan['name'] }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- BLOQUES INFORMATIVOS (config/landing.php) --}}
    <section class="bg-white px-4 py-20 sm:px-6 sm:py-24">
        <div class="mx-auto grid max-w-6xl gap-10 md:grid-cols-3">
            @foreach (config('landing.blocks') as $block)
                <div>
                    <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-rojo/10 text-rojo">
                        <x-dynamic-component :component="'icon.'.$block['icon']" />
                    </div>
                    <h3 class="mt-5 text-lg font-bold">{{ $block['title'] }}</h3>
                    <p class="mt-2 text-stone-600">{{ $block['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- PREGUNTAS FRECUENTES --}}
    <section class="px-4 py-20 sm:px-6 sm:py-24">
        <div class="mx-auto max-w-3xl">
            <h2 class="text-center text-3xl font-extrabold tracking-tight">Preguntas frecuentes</h2>
            <div class="mt-10 divide-y divide-stone-200 rounded-3xl bg-white px-6 ring-1 ring-stone-200">
                @foreach (config('landing.faq') as $item)
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                            {{ $item['q'] }}
                            <span class="text-xl text-rojo transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 text-stone-600">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <x-site-footer />

</x-layouts.site>
