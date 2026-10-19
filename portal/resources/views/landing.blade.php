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
                <x-help-link tone="light" />
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
                                <span class="min-w-0"><strong class="break-all" x-text="result.email"></strong> está libre</span>
                                <span x-show="result.requires_paid_plan" class="w-full text-sm text-amarillo-claro"
                                      x-text="'Nombre corto: +' + euros(result.surcharge_cents) + '/mes, con un plan de pago'"></span>
                            </p>
                            <p x-show="!result.available" class="font-medium text-amarillo-claro" x-text="result.message"></p>
                            <div x-show="result.suggestions.length" class="mt-3 flex flex-wrap items-center justify-center gap-2">
                                <span class="text-sm text-white/75">Prueba con:</span>
                                <template x-for="suggestion in result.suggestions" :key="suggestion">
                                    <button type="button" @click="pick(suggestion)" x-text="suggestion" data-ph-mask
                                            class="ph-no-capture max-w-full break-all rounded-full bg-white/90 px-3 py-1 text-sm font-semibold text-tinta transition hover:bg-white"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </form>

            @if ($mailboxCount)
                <p class="mt-2 text-sm font-medium text-white/80">{{ __(config('landing.counter.text'), ['count' => number_format($mailboxCount, 0, ',', '.'), 'domain' => $mainDomain]) }}</p>
            @endif
        </div>

        <a href="#planes" class="absolute inset-x-0 bottom-6 mx-auto w-fit text-sm font-medium text-white/80 transition hover:text-white">
            Ver planes ↓
        </a>
    </section>

    {{-- ASÍ TE VERÁN: bandeja de entrada con la dirección elegida arriba, y el alta en 3 pasos (config/landing.php) --}}
    @php $preview = config('landing.preview'); @endphp
    <section class="bg-white px-4 py-20 sm:px-6 sm:py-24">
        <div class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 md:grid-cols-2 md:gap-16">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $preview['title'] }}</h2>
                <p class="mt-3 text-lg text-stone-600 text-pretty">{{ $preview['subtitle'] }}</p>

                <h3 class="mt-10 text-sm font-bold uppercase tracking-[0.15em] text-rojo">{{ $preview['steps_title'] }}</h3>
                <ol class="mt-5 space-y-6">
                    @foreach ($preview['steps'] as $step)
                        <li class="flex gap-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-rojo font-bold text-white">{{ $loop->iteration }}</span>
                            <div>
                                <p class="font-bold">{{ $step['title'] }}</p>
                                <p class="mt-1 text-stone-600">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Con la dirección que haya elegido el visitante (por eso ph-no-capture y data-ph-mask) --}}
            <div x-data="{ example: @js($preview['example'].'@'.$mainDomain),
                           get address() { return $store.signup.email || this.example },
                           get local() { return this.address.split('@')[0] },
                           get name() { return this.local.split(/[.\-_]+/).filter(Boolean).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') } }"
                 class="ph-no-capture space-y-8">
                <div class="overflow-hidden rounded-3xl bg-white shadow-2xl shadow-stone-300/50 ring-1 ring-stone-200"
                     role="img" :aria-label="'Ejemplo de bandeja de entrada con un correo de ' + address">
                    <div class="flex items-center gap-2 border-b border-stone-200 bg-stone-50 px-5 py-3" aria-hidden="true">
                        <span class="size-3 rounded-full bg-stone-300"></span><span class="size-3 rounded-full bg-stone-300"></span><span class="size-3 rounded-full bg-stone-300"></span>
                        <span class="ml-3 text-sm font-semibold text-stone-500">Recibidos</span>
                    </div>
                    <ul class="divide-y divide-stone-100" aria-hidden="true">
                        @foreach ($preview['inbox'] as $mail)
                            @php $mine = $loop->first; @endphp
                            <li @class(['flex gap-4 px-5 py-4', 'border-l-4 border-rojo bg-amarillo/10' => $mine, 'opacity-60' => ! $mine])>
                                <span @class(['flex size-10 shrink-0 items-center justify-center rounded-full font-bold',
                                              'bg-rojo text-white' => $mine, 'bg-stone-200 text-stone-500' => ! $mine])
                                      @if ($mine) x-text="name.charAt(0)" data-ph-mask @endif>{{ $mine ? '' : mb_substr($mail['from'], 0, 1) }}</span>
                                <div class="min-w-0 flex-1">
                                    @if ($mine)
                                        <p class="truncate text-sm font-semibold text-stone-600" data-ph-mask x-text="name"></p>
                                        <p class="truncate text-base sm:text-lg" data-ph-mask><span class="font-semibold" x-text="local"></span><span class="font-extrabold text-rojo">{{ '@'.$mainDomain }}</span></p>
                                    @else
                                        <p class="truncate font-semibold">{{ $mail['from'] }}</p>
                                    @endif
                                    <p class="truncate">{{ $mail['subject'] }}</p>
                                    <p class="truncate text-sm text-stone-500">{{ $mail['snippet'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Ilustración: alguien da su correo en una ventanilla de la Administración --}}
                <figure class="relative" role="img" :aria-label="'Ilustración: una persona da su correo, ' + address + ', en una ventanilla'">
                    <div class="relative z-10 ml-[4%] w-fit max-w-[92%] rounded-3xl bg-white px-5 py-3.5 shadow-lg ring-2 ring-tinta" aria-hidden="true">
                        <p class="text-sm font-semibold text-stone-500">{{ $preview['bubble'] }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-1 gap-y-1 text-lg font-bold leading-tight sm:text-xl" data-ph-mask>
                            <span class="break-all" x-text="local"></span><span class="flag-label whitespace-nowrap rounded-md px-2 py-1.5 font-extrabold text-rojo-oscuro sm:text-2xl">{{ '@'.$mainDomain }}</span>
                        </p>
                        {{-- Rabito del bocadillo, hacia la cabeza de la persona --}}
                        <svg class="absolute -bottom-[18px] left-[18%] h-5 w-7" viewBox="0 0 28 20" aria-hidden="true">
                            <path d="M2 0 L14 18 L22 0" fill="#fff" stroke="var(--color-tinta)" stroke-width="2.5" stroke-linejoin="round"/>
                            <rect x="0" y="-3" width="28" height="4.5" fill="#fff"/>
                        </svg>
                    </div>
                    {{-- Viñeta de tebeo (como los anuncios de X), con la tinta suave para no chocar con la página:
                         quien da el correo, de espaldas en primer plano; al fondo, el funcionario se lleva un susto --}}
                    <svg viewBox="0 0 400 150" class="-mt-1 w-full" aria-hidden="true"
                         stroke="#3f3a36" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round">
                        <defs><clipPath id="ventanilla"><rect width="400" height="150" rx="24"/></clipPath></defs>
                        <g clip-path="url(#ventanilla)">
                            <rect width="400" height="150" fill="#f3ede4" stroke="none"/>
                            {{-- Bandera en la pared --}}
                            <path d="M158 18 V70" fill="none"/>
                            <path d="M158 22 q14 -5 28 0 t28 0 v30 q-14 -5 -28 0 t-28 0z" fill="#F1BF00"/>
                            <path d="M158 22 q14 -5 28 0 t28 0 v7.5 q-14 -5 -28 0 t-28 0z" fill="#AA151B"/>
                            <path d="M158 44.5 q14 -5 28 0 t28 0 v7.5 q-14 -5 -28 0 t-28 0z" fill="#AA151B"/>
                            {{-- Cartel --}}
                            <rect x="230" y="10" width="146" height="22" rx="4" fill="#3f3a36"/>
                            <text x="303" y="25" text-anchor="middle" font-size="9.5" font-weight="700" letter-spacing="1" fill="#fff" stroke="none" font-family="inherit">ATENCIÓN AL CIUDADANO</text>
                            {{-- Funcionario: redondo, pelo rosa y un susto de los gordos --}}
                            <g transform="translate(0 10)">
                            <ellipse cx="296" cy="124" rx="50" ry="42" fill="#6b7d8f"/>
                            <path d="M284 86 l12 16 l12 -16" fill="#fff"/>
                            <path d="M296 102 l-4 10 l4 8 l4 -8z" fill="#AA151B"/>
                            <circle cx="296" cy="64" r="22" fill="#e9b796"/>
                            <path d="M273 70 q-8 -40 23 -41 q31 1 23 41 q-4 -2 -5 -10 q-2 -10 -9 -16 q-12 7 -26 4 q-5 7 -6 22z" fill="#f472b6"/>
                            <path d="M281 52 q5 -5 10 -1 M301 51 q5 -4 10 1" fill="none"/>
                            <circle cx="287" cy="61" r="5.2" fill="#fff"/><circle cx="305" cy="61" r="5.2" fill="#fff"/>
                            <circle cx="287" cy="62" r="1.9" fill="#3f3a36" stroke="none"/><circle cx="305" cy="62" r="1.9" fill="#3f3a36" stroke="none"/>
                            <ellipse cx="296" cy="76" rx="5" ry="6.5" fill="#7d0f14"/>
                            <path d="M322 46 q4 6 0 9 q-4 -3 0 -9z" fill="#bfdbfe" stroke-width="1.6"/>
                            <path d="M327 60 q3.5 5 0 8 q-3.5 -3 0 -8z" fill="#bfdbfe" stroke-width="1.6"/>
                            <path d="M262 52 l-9 -4 M260 62 l-10 0 M262 72 l-9 4" fill="none" stroke-width="2"/>
                            {{-- Ordenador y sello --}}
                            <rect x="196" y="70" width="46" height="34" rx="4" fill="#3d3a36"/>
                            <path d="M214 104 v8 h10 v-8" fill="#3d3a36"/>
                            <path d="M340 104 q-6 -10 2 -16 q6 -2 8 4 l-2 12z" fill="#e9b796"/>
                            <rect x="338" y="74" width="12" height="13" rx="3" fill="#7d0f14"/>
                            <rect x="335" y="86" width="18" height="6" rx="1.5" fill="#3f3a36"/>
                            <path d="M332 70 l-4 -4 M356 70 l4 -4 M344 66 v-5" fill="none" stroke-width="1.8"/>
                            </g>
                            {{-- Mostrador con papeles --}}
                            <rect x="150" y="112" width="250" height="9" rx="3" fill="#b8b2ac"/>
                            <rect x="158" y="121" width="242" height="34" fill="#ddd8d2"/>
                            <path d="M252 112 l4 -5 h26 l-4 5" fill="#fff" stroke-width="1.8"/>
                            {{-- Quien da el correo, de espaldas: pelo con mechones, orejas y jersey rojo --}}
                            <ellipse cx="96" cy="172" rx="90" ry="34" fill="#AA151B"/>
                            <path d="M62 140 q34 14 68 0" fill="none" stroke="#7d0f14"/>
                            <ellipse cx="50" cy="110" rx="9" ry="13" fill="#d9a07a"/>
                            <ellipse cx="142" cy="110" rx="9" ry="13" fill="#d9a07a"/>
                            <circle cx="96" cy="106" r="46" fill="#2b2523"/>
                            <path d="M70 80 q26 -18 54 -2 M80 70 q16 -8 30 -4 M64 100 q4 -16 18 -24 M128 100 q-4 -16 -18 -24" fill="none" stroke="#5b4a3e" stroke-width="2"/>
                        </g>
                        <rect x="1.2" y="1.2" width="397.6" height="147.6" rx="23" fill="none"/>
                    </svg>
                </figure>
            </div>
        </div>
    </section>

    {{-- PLANES (salen de la BD) --}}
    @php
        $hasYearly = $plans->contains(fn ($p) => ! $p['is_free'] && $p['interval'] === 'year');
    @endphp
    <section id="planes" class="scroll-mt-4 px-4 py-20 sm:px-6 sm:py-28" x-data="{ yearly: false, setYearly(v) { this.yearly = v } }">
        <div class="mx-auto max-w-6xl">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ config('landing.plans.title') }}</h2>
                <p class="mt-3 text-lg text-stone-600">{{ config('landing.plans.subtitle') }}</p>
                <p x-cloak x-show="$store.signup.email" data-ph-mask class="mt-6 inline-flex max-w-full items-center gap-2 rounded-3xl bg-amarillo/25 px-4 py-2 font-medium">
                    <x-icon.check class="size-5 shrink-0 text-rojo" />
                    <span class="min-w-0">Tu dirección: <strong class="break-all" x-text="$store.signup.email"></strong></span>
                </p>
            </div>

            @if ($plans->isEmpty())
                <p class="mt-12 text-center text-stone-500">Muy pronto publicaremos los planes.</p>
            @else
                {{-- Con planes anuales, se ve una tanda u otra (el gratis, siempre): cuentan los de una tanda --}}
                @php
                    $perRow = $hasYearly ? $plans->filter(fn ($p) => $p['interval'] !== 'year')->count() : $plans->count();
                @endphp
                @if ($hasYearly)
                    <x-billing-toggle class="mt-10" />
                @endif
                <div @class([
                    'mx-auto grid gap-6',
                    'mt-14' => ! $hasYearly, 'mt-12' => $hasYearly,
                    'max-w-md' => $perRow === 1,
                    'max-w-4xl md:grid-cols-2' => $perRow === 2,
                    'md:grid-cols-3' => $perRow >= 3,
                ])>
                    @foreach ($plans as $plan)
                        @php
                            $per = $plan['interval'] === 'year' ? '/año' : '/mes';
                            $hasOffer = $plan['offer'] !== null;
                            $yearlyCard = ! $plan['is_free'] && $plan['interval'] === 'year';
                        @endphp
                        <article @if ($hasYearly && ! $plan['is_free']) x-show="{{ $yearlyCard ? 'yearly' : '! yearly' }}" @if ($yearlyCard) x-cloak @endif @endif @class([
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
                                    @if ($yearlyCard)
                                        <p class="mt-1 text-sm text-stone-500">Sale a {{ Money::format(intdiv($plan['effective_price_cents'], 12)) }}/mes.</p>
                                    @endif
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

    {{-- PASARTE ES FÁCIL (config/landing.php) --}}
    @php $switch = config('landing.switch'); @endphp
    <section class="px-4 py-20 sm:px-6 sm:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $switch['title'] }}</h2>
                <p class="mt-3 text-lg text-stone-600 text-pretty">{{ $switch['subtitle'] }}</p>
            </div>
            <div class="mt-12 grid items-start gap-6 md:grid-cols-3">
                @foreach ($switch['items'] as $item)
                    <div class="rounded-3xl bg-white p-7 ring-1 ring-stone-200">
                        <span class="flex size-9 items-center justify-center rounded-full bg-rojo/10 font-bold text-rojo">{{ $loop->iteration }}</span>
                        <h3 class="mt-4 text-lg font-bold">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-stone-600">{{ $item['text'] }}</p>
                        @foreach ($item['guides'] ?? [] as $guide)
                            <details class="group mt-4 rounded-2xl bg-stone-50 px-4 py-3">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold">
                                    {{ $guide['name'] }}
                                    <span class="text-xl text-rojo transition group-open:rotate-45" aria-hidden="true">+</span>
                                </summary>
                                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-stone-600">
                                    @foreach ($guide['steps'] as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                </ol>
                            </details>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PREGUNTAS FRECUENTES --}}
    <section class="bg-white px-4 py-20 sm:px-6 sm:py-24">
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

    {{-- Ayuda flotante: aparece al bajar por la home (Fase 7) --}}
    <a href="{{ route('support') }}" x-data="{ shown: false }" @scroll.window.throttle.150ms="shown = window.scrollY > 600"
       x-show="shown" x-cloak x-transition.opacity.duration.200ms
       class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-tinta px-4 py-3 font-semibold text-white shadow-xl shadow-black/20 ring-1 ring-white/10 transition hover:bg-rojo sm:bottom-6 sm:right-6">
        <x-icon.help class="size-5" />
        ¿Necesitas ayuda?
    </a>

</x-layouts.site>
