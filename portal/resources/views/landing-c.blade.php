@php
    use App\Support\Money;
    use App\Support\PlanGroups;
    $mainDomain = $domains[0] ?? 'unagrandeylibre.es';
@endphp

{{-- HOME C (variante b del experimento `home-c`; en local, también en /home-c): misma lógica que la home, estética de producto "pro":
     fondo oscuro, la bandera como luz y no como fondo, el producto en primer plano y la infraestructura a la vista. --}}
<x-layouts.site :structured-data="$structuredData">
<style>
    .hc { --hc-bg: #0b0a09; --hc-panel: #141210; --hc-line: rgb(255 255 255 / 0.08); background: var(--hc-bg); color: #f5f5f4; }
    .hc ::selection { background: var(--color-amarillo); color: var(--color-tinta); }

    /* Rejilla de fondo que se desvanece hacia los bordes */
    .hc-grid {
        background-image:
            linear-gradient(to right, rgb(255 255 255 / 0.045) 1px, transparent 1px),
            linear-gradient(to bottom, rgb(255 255 255 / 0.045) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 75%);
    }

    /* Luces de la bandera: rojo, amarillo, rojo */
    .hc-aurora { position: absolute; border-radius: 9999px; filter: blur(90px); pointer-events: none; }
    .hc-aurora.r { background: var(--color-rojo); opacity: .55; }
    .hc-aurora.a { background: var(--color-amarillo); opacity: .28; }
    @media (prefers-reduced-motion: no-preference) {
        .hc-aurora { animation: hc-float 18s ease-in-out infinite alternate; }
        .hc-aurora.a { animation-duration: 24s; animation-delay: -6s; }
    }
    @keyframes hc-float { to { transform: translate3d(4%, 6%, 0) scale(1.12); } }

    .hc-gradient-text {
        background: linear-gradient(100deg, #fff 0%, var(--color-amarillo-claro) 20%, var(--color-amarillo) 35%, #ff6b5e 50%, var(--color-amarillo) 65%, var(--color-amarillo-claro) 80%, #fff 100%);
        background-size: 200% 100%;
        -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    @media (prefers-reduced-motion: no-preference) {
        .hc-gradient-text { animation: hc-shine 7s ease-in-out infinite alternate; }
    }
    @keyframes hc-shine { to { background-position: 100% 0; } }

    /* Hero: las tres luces de la bandera en vertical (rojo, amarillo, rojo). Respiran al unísono: crecen, bajan un poco
       y ganan luz a la vez, y vuelven. */
    .hc-wave { position: absolute; border-radius: 9999px; filter: blur(90px); pointer-events: none; }
    .hc-wave.r { background: var(--color-rojo); opacity: .55; }
    .hc-wave.a { background: var(--color-amarillo); opacity: .5; }
    @media (prefers-reduced-motion: no-preference) {
        .hc-waves { animation: hc-breathe 6s ease-in-out infinite; transform-origin: 50% 0; }
    }
    @keyframes hc-breathe {
        0%, 100% { transform: translate3d(0, 0, 0) scale(1); opacity: .8; }
        50%      { transform: translate3d(0, 4rem, 0) scale(1.15); opacity: 1; }
    }

    /* Línea de la bandera que recorre el borde (input del hero) */
    .hc-flagline { position: absolute; inset: -1px; border-radius: inherit; padding: 1.5px; pointer-events: none;
        background: conic-gradient(from var(--hc-a, 0deg), transparent 0 55%, var(--color-rojo) 65%, var(--color-amarillo) 75%, var(--color-rojo) 85%, transparent 95%);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; opacity: .9; }
    @property --hc-a { syntax: '<angle>'; inherits: false; initial-value: 0deg; }
    @media (prefers-reduced-motion: no-preference) {
        .hc-flagline { animation: hc-spin 6s linear infinite; }
    }
    @keyframes hc-spin { to { --hc-a: 360deg; } }

    /* El @dominio del input: en amarillo, con brillo */
    .hc-domain { background: linear-gradient(90deg, var(--color-amarillo-claro), var(--color-amarillo) 45%, #ff8a3d);
                 -webkit-background-clip: text; background-clip: text; color: transparent;
                 filter: drop-shadow(0 0 14px rgb(241 191 0 / .35)); }

    /* Borde con degradado (tarjetas destacadas, input del hero) */
    .hc-ring { position: relative; }
    .hc-ring::before {
        content: ''; position: absolute; inset: 0; padding: 1px; border-radius: inherit; pointer-events: none;
        background: linear-gradient(140deg, rgb(255 255 255 / 0.22), rgb(255 255 255 / 0.04) 40%, rgb(241 191 0 / 0.35) 100%);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor; mask-composite: exclude;
    }
    .hc-ring.is-hot::before { background: linear-gradient(140deg, var(--color-amarillo), var(--color-rojo) 50%, rgb(255 255 255 / 0.1)); }

    .hc-card { background: linear-gradient(180deg, rgb(255 255 255 / 0.045), rgb(255 255 255 / 0.015)); border-radius: 1.5rem; }

    /* Foco que sigue al ratón en las tarjetas */
    .hc-spot { --x: 50%; --y: 50%; }
    .hc-spot::after {
        content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none; opacity: 0; transition: opacity .3s;
        background: radial-gradient(420px circle at var(--x) var(--y), rgb(241 191 0 / 0.09), transparent 45%);
    }
    .hc-spot:hover::after { opacity: 1; }

    /* Ventana del producto, ligeramente inclinada */
    .hc-window { transform: perspective(2200px) rotateX(9deg); transform-origin: top center; transition: transform 1.2s cubic-bezier(.2,.7,.1,1); }
    .hc-window.is-flat { transform: perspective(2200px) rotateX(0deg); }

    /* Aparición al hacer scroll */
    .hc-reveal { opacity: 0; transform: translateY(24px); transition: opacity .8s ease, transform .8s cubic-bezier(.2,.7,.1,1); }
    .hc-reveal.is-in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .hc-reveal { opacity: 1; transform: none; }
        .hc-window { transform: none; }
    }

    .hc-marquee { mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent); }
    @media (prefers-reduced-motion: no-preference) {
        .hc-marquee-track { animation: hc-marquee 32s linear infinite; }
    }
    @keyframes hc-marquee { to { transform: translateX(-50%); } }

    .hc-pulse { box-shadow: 0 0 0 0 rgb(74 222 128 / .6); animation: hc-pulse 2s infinite; }
    @keyframes hc-pulse { 70% { box-shadow: 0 0 0 8px rgb(74 222 128 / 0); } 100% { box-shadow: 0 0 0 0 rgb(74 222 128 / 0); } }

    .hc-caret::after { content: '▍'; color: var(--color-amarillo); animation: hc-blink 1s steps(1) infinite; }
    @keyframes hc-blink { 50% { opacity: 0; } }

    .hc details > summary::-webkit-details-marker { display: none; }
</style>

<div class="hc relative overflow-x-clip" x-data
     x-init="
        const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target) } }), { threshold: .15 });
        $el.querySelectorAll('.hc-reveal').forEach(n => io.observe(n));
        $el.querySelectorAll('.hc-spot').forEach(n => n.addEventListener('pointermove', ev => {
            const r = n.getBoundingClientRect(); n.style.setProperty('--x', (ev.clientX - r.left) + 'px'); n.style.setProperty('--y', (ev.clientY - r.top) + 'px');
        }));
     ">

    {{-- CABECERA fija, de cristal --}}
    <header x-data="{ scrolled: false }" @scroll.window.throttle.100ms="scrolled = window.scrollY > 24"
            :class="scrolled ? 'bg-[#0b0a09]/75 backdrop-blur-xl border-white/10' : 'border-transparent'"
            class="fixed inset-x-0 top-0 z-50 border-b transition-colors duration-300">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-[15px] font-bold tracking-tight">
                <span class="flex h-5 w-5 flex-col overflow-hidden rounded-[5px] ring-1 ring-white/20" aria-hidden="true">
                    <span class="h-1/4 bg-rojo"></span><span class="h-1/2 bg-amarillo"></span><span class="h-1/4 bg-rojo"></span>
                </span>
                <span>unagrandeylibre<span class="text-white/40">.es</span></span>
            </a>
            <nav class="hidden items-center gap-1 text-sm text-white/60 md:flex">
                <a href="#producto" class="rounded-full px-3 py-1.5 transition hover:text-white">Producto</a>
                <a href="#infraestructura" class="rounded-full px-3 py-1.5 transition hover:text-white">Infraestructura</a>
                <a href="#planes" class="rounded-full px-3 py-1.5 transition hover:text-white">Precios</a>
                <a href="#faq" class="rounded-full px-3 py-1.5 transition hover:text-white">Preguntas</a>
            </nav>
            <div class="flex items-center gap-2 text-sm">
                <a href="https://webmail.{{ $mainDomain }}" class="hidden rounded-full px-3 py-1.5 text-white/60 transition hover:text-white sm:inline-block">Webmail</a>
                @auth
                    <a href="{{ route('account') }}" class="rounded-full bg-white px-4 py-1.5 font-semibold text-tinta transition hover:bg-amarillo-claro">Mi cuenta</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-3 py-1.5 text-white/80 transition hover:text-white">Entrar</a>
                    <a href="#top" class="rounded-full bg-white px-4 py-1.5 font-semibold text-tinta transition hover:bg-amarillo-claro">Empezar</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- HERO --}}
    <section id="top" class="relative isolate px-4 pb-10 pt-36 sm:px-6 sm:pt-44">
        <div class="hc-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="hc-waves absolute inset-0 -z-10" aria-hidden="true">
            <div class="hc-wave r left-[8%] top-[-6rem] h-[28rem] w-[28rem]"></div>
            <div class="hc-wave a left-[30%] top-[-2rem] h-[26rem] w-[40%]"></div>
            <div class="hc-wave r right-[6%] top-[-4rem] h-[26rem] w-[26rem]"></div>
        </div>

        <div class="mx-auto max-w-4xl text-center">
            <a href="{{ route('status') }}" class="inline-flex items-center gap-2.5 rounded-full border border-white/10 bg-white/[0.04] py-1.5 pl-3 pr-4 text-[13px] text-white/70 backdrop-blur transition hover:border-white/20 hover:text-white">
                <span class="hc-pulse size-2 rounded-full bg-green-400"></span>
                Servidores en la Unión Europea · vigilados cada 10 minutos
                <span class="text-white/40" aria-hidden="true">→</span>
            </a>

            <h1 class="mt-8 text-[2.75rem] font-extrabold leading-[1.02] tracking-[-0.04em] text-balance sm:text-7xl lg:text-[5.5rem]">
                Tu correo,<br class="hidden sm:block"> <span class="hc-gradient-text">hecho en España.</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white/60 text-pretty sm:text-xl">
                Una dirección propia, privada y sin publicidad, sobre una infraestructura que cuidamos nosotros mismos. Elige tu nombre:
            </p>

            {{-- El mismo comprobador de nombres que la home --}}
            <form x-data="nameField(@js($domains))" @submit.prevent="submit()" class="mx-auto mt-10 w-full max-w-2xl text-left" novalidate>
                <div class="relative rounded-2xl bg-white/[0.06] p-1.5 shadow-2xl shadow-black/50 backdrop-blur-xl transition focus-within:bg-white/[0.09]">
                    <span class="hc-flagline" aria-hidden="true"></span>
                    <div class="flex flex-col gap-1.5 sm:flex-row">
                        <div class="flex min-w-0 flex-1 items-center">
                            <label for="nombre" class="sr-only">Nombre de tu dirección de correo</label>
                            <input id="nombre" x-ref="input" x-model="local" type="text" name="nombre"
                                   placeholder="tunombre" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false"
                                   maxlength="64" enterkeyhint="go"
                                   class="min-w-0 flex-1 bg-transparent py-3.5 pl-4 pr-1 text-right font-mono text-lg text-white outline-none placeholder:text-white/30 sm:text-xl"
                                   :aria-invalid="result && !result.available"
                                   aria-describedby="nombre-estado">
                            @if (count($domains) > 1)
                                <label for="dominio" class="sr-only">Dominio</label>
                                <select id="dominio" x-model="domain" class="mr-2 rounded-lg bg-amarillo/10 py-2 pl-2 pr-1 font-mono text-base font-semibold text-amarillo outline-none">
                                    @foreach ($domains as $domain)
                                        <option value="{{ $domain }}" class="text-tinta">{{ '@'.$domain }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="hc-domain whitespace-nowrap pr-4 font-mono text-base font-semibold sm:text-xl">{{ '@'.$mainDomain }}</span>
                            @endif
                        </div>
                        <button type="submit"
                                class="group inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3.5 text-base font-semibold text-tinta transition hover:bg-amarillo focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amarillo">
                            Consigue tu cuenta
                            <span class="transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
                        </button>
                    </div>
                </div>

                <div id="nombre-estado" aria-live="polite" data-ph-mask class="mt-4 min-h-[3.5rem] text-center text-[15px]">
                    <p x-cloak x-show="state === 'checking'" class="font-mono text-white/50">comprobando…</p>
                    <p x-cloak x-show="state === 'limited'" class="text-amarillo-claro">Demasiadas comprobaciones seguidas. Espera un minuto.</p>
                    <p x-cloak x-show="state === 'error'" class="text-amarillo-claro">No hemos podido comprobarlo. Inténtalo de nuevo.</p>
                    <template x-if="state === 'done' && result">
                        <div>
                            <p x-show="result.available" class="inline-flex flex-wrap items-center justify-center gap-x-2 gap-y-1 rounded-full border border-green-400/25 bg-green-400/10 px-4 py-1.5 text-green-100">
                                <x-icon.check class="size-4 text-green-400" />
                                <span class="min-w-0"><strong class="break-all font-mono font-medium" x-text="result.email"></strong> está libre</span>
                                <span x-show="result.requires_paid_plan" class="w-full text-sm text-amarillo-claro"
                                      x-text="'Nombre corto: +' + euros(result.surcharge_cents) + '/mes, con un plan de pago'"></span>
                            </p>
                            <p x-show="!result.available" class="text-amarillo-claro" x-text="result.message"></p>
                            <div x-show="result.suggestions.length" class="mt-3 flex flex-wrap items-center justify-center gap-2">
                                <span class="text-sm text-white/50">Prueba con:</span>
                                <template x-for="suggestion in result.suggestions" :key="suggestion">
                                    <button type="button" @click="pick(suggestion)" x-text="suggestion" data-ph-mask
                                            class="ph-no-capture max-w-full break-all rounded-full border border-white/15 bg-white/5 px-3 py-1 font-mono text-sm text-white transition hover:border-white/40"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </form>

            @if ($mailboxCount)
                <p class="text-sm text-white/50">{{ __(config('landing.counter.text'), ['count' => number_format($mailboxCount, 0, ',', '.'), 'domain' => $mainDomain]) }}</p>
            @endif

            <ul class="mx-auto mt-4 flex max-w-2xl flex-wrap items-center justify-center gap-x-6 gap-y-2 font-mono text-xs uppercase tracking-widest text-white/35">
                <li>Desde 0,42 €/mes</li><li aria-hidden="true">·</li><li>Sin permanencia</li><li aria-hidden="true">·</li><li>IVA incluido</li>
            </ul>
        </div>

        {{-- EL PRODUCTO: un webmail con la dirección del visitante --}}
        <div id="producto" class="relative mx-auto mt-16 max-w-6xl scroll-mt-24"
             x-data="{ flat: false, example: @js('tunombre@'.$mainDomain),
                       get address() { return $store.signup.email || this.example },
                       get local() { return this.address.split('@')[0] },
                       get name() { return this.local.split(/[.\-_]+/).filter(Boolean).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') } }"
             x-init="new IntersectionObserver(([e]) => flat = e.intersectionRatio > .5, { threshold: [0, .5, 1] }).observe($el)">
            <div class="absolute inset-x-10 -top-6 h-40 rounded-full bg-amarillo/20 blur-3xl" aria-hidden="true"></div>
            <div :class="flat && 'is-flat'" class="hc-window hc-ring ph-no-capture relative overflow-hidden rounded-2xl bg-[#121110] shadow-[0_40px_120px_-20px_rgb(0_0_0/0.9)]"
                 role="img" :aria-label="'Ejemplo del webmail con la dirección ' + address">
                {{-- Barra de la ventana --}}
                <div class="flex items-center gap-2 border-b border-white/[0.07] bg-white/[0.03] px-4 py-3" aria-hidden="true">
                    <span class="size-3 rounded-full bg-[#ff5f57]"></span><span class="size-3 rounded-full bg-[#febc2e]"></span><span class="size-3 rounded-full bg-[#28c840]"></span>
                    <div class="mx-auto flex items-center gap-2 rounded-md bg-white/[0.05] px-3 py-1 font-mono text-xs text-white/45">
                        <svg viewBox="0 0 16 16" class="size-3 fill-green-400/80"><path d="M8 1a3.5 3.5 0 0 0-3.5 3.5V6H4a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-.5V4.5A3.5 3.5 0 0 0 8 1Zm2 5H6V4.5a2 2 0 1 1 4 0V6Z"/></svg>
                        webmail.{{ $mainDomain }}
                    </div>
                    <span class="w-[60px]"></span>
                </div>

                <div class="grid grid-cols-1 text-sm md:grid-cols-[200px_320px_1fr]" aria-hidden="true">
                    {{-- Carpetas --}}
                    <aside class="hidden border-r border-white/[0.07] p-4 md:block">
                        <div class="rounded-lg bg-white px-3 py-2 text-center text-[13px] font-semibold text-tinta">Redactar</div>
                        <ul class="mt-5 space-y-0.5 text-white/55">
                            <li class="flex justify-between rounded-md bg-white/[0.07] px-3 py-1.5 font-medium text-white">Recibidos <span class="font-mono text-amarillo">3</span></li>
                            <li class="px-3 py-1.5">Enviados</li><li class="px-3 py-1.5">Borradores</li><li class="flex justify-between px-3 py-1.5">Spam <span class="font-mono text-white/30">0</span></li><li class="px-3 py-1.5">Papelera</li>
                        </ul>
                        <div class="mt-6 rounded-lg border border-white/[0.07] p-3">
                            <p class="text-[11px] uppercase tracking-wider text-white/35">Espacio</p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full w-[12%] rounded-full bg-gradient-to-r from-amarillo to-rojo"></div></div>
                            <p class="mt-1.5 font-mono text-[11px] text-white/40">12 % usado</p>
                        </div>
                    </aside>

                    {{-- Lista --}}
                    <ul class="divide-y divide-white/[0.06] border-r border-white/[0.07]">
                        @foreach (config('landing.preview.inbox') as $mail)
                            @php $mine = $loop->first; @endphp
                            <li @class(['relative px-4 py-3.5', 'bg-white/[0.05]' => $mine])>
                                @if ($mine)<span class="absolute inset-y-0 left-0 w-0.5 bg-amarillo"></span>@endif
                                <div class="flex items-baseline justify-between gap-3">
                                    <p @class(['truncate font-semibold', 'text-white' => $mine, 'text-white/60' => ! $mine])
                                       @if ($mine) x-text="name" data-ph-mask @endif>{{ $mine ? '' : $mail['from'] }}</p>
                                    <span class="shrink-0 font-mono text-[11px] text-white/35">{{ ['09:41', 'ayer', 'lun'][$loop->index] ?? '' }}</span>
                                </div>
                                <p @class(['truncate', 'text-white/90' => $mine, 'text-white/50' => ! $mine])>{{ $mail['subject'] }}</p>
                                <p class="truncate text-white/35">{{ $mail['snippet'] }}</p>
                            </li>
                        @endforeach
                        <li class="h-24 md:h-40"></li>
                    </ul>

                    {{-- Lectura --}}
                    <div class="hidden p-6 md:block">
                        <p class="text-xl font-semibold text-white">{{ config('landing.preview.inbox.0.subject') }}</p>
                        <div class="mt-4 flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-amarillo to-rojo font-bold text-tinta" x-text="name.charAt(0)" data-ph-mask></span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white" x-text="name" data-ph-mask></p>
                                <p class="truncate font-mono text-[13px]" data-ph-mask><span class="text-white/70" x-text="local"></span><span class="text-amarillo">{{ '@'.$mainDomain }}</span></p>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-1.5 font-mono text-[11px]">
                            @foreach (['SPF' => 'pass', 'DKIM' => 'pass', 'DMARC' => 'pass', 'TLS' => 'cifrado'] as $k => $v)
                                <span class="rounded-md border border-green-400/20 bg-green-400/[0.07] px-2 py-0.5 text-green-300/90">{{ $k }} <span class="text-green-300/50">{{ $v }}</span></span>
                            @endforeach
                        </div>
                        <div class="mt-6 space-y-2.5 text-white/55">
                            <p>¡Hola!</p>
                            <p>{{ config('landing.preview.inbox.0.snippet') }} Ya tengo correo nuevo, apúntatelo.</p>
                            <div class="space-y-2 pt-2"><div class="h-2 w-11/12 rounded bg-white/[0.06]"></div><div class="h-2 w-4/5 rounded bg-white/[0.06]"></div><div class="h-2 w-2/3 rounded bg-white/[0.06]"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#0b0a09] to-transparent" aria-hidden="true"></div>
        </div>
    </section>

    {{-- FUNCIONA CON TODO --}}
    <section class="px-4 py-14 sm:px-6">
        <p class="text-center font-mono text-xs uppercase tracking-[0.25em] text-white/35">Funciona con las apps que ya usas</p>
        <div class="hc-marquee mx-auto mt-8 max-w-5xl overflow-hidden">
            <div class="hc-marquee-track flex w-max gap-14 text-xl font-semibold tracking-tight text-white/40">
                @foreach ([1, 2] as $copy)
                    @foreach (['Mail de iPhone', 'Gmail para Android', 'Outlook', 'Thunderbird', 'Mail de Mac', 'Webmail', 'Cualquier app IMAP'] as $app)
                        <span class="whitespace-nowrap" @if ($copy === 2) aria-hidden="true" @endif>{{ $app }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>

    {{-- POR QUÉ: rejilla tipo bento --}}
    @php $blocks = config('landing.blocks'); @endphp
    <section class="px-4 py-20 sm:px-6 sm:py-28">
        <div class="mx-auto max-w-6xl">
            <div class="hc-reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-amarillo">Por qué nosotros</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-[-0.03em] sm:text-5xl">El correo como debería ser. <span class="text-white/40">Tuyo, y de nadie más.</span></h2>
            </div>

            <div class="mt-14 grid gap-4 md:grid-cols-6">
                {{-- Grande: privacidad --}}
                <div class="hc-reveal hc-card hc-ring hc-spot relative overflow-hidden p-8 md:col-span-4 md:row-span-2">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-white/[0.06] text-amarillo"><x-icon.shield class="size-5" /></div>
                    <h3 class="mt-6 text-2xl font-bold tracking-tight">{{ $blocks[1]['title'] }}</h3>
                    <p class="mt-3 max-w-md text-white/55">{{ $blocks[1]['text'] }}</p>
                    {{-- Visual: un sobre cifrado --}}
                    <div class="mt-10 rounded-xl border border-white/[0.07] bg-black/40 p-5 font-mono text-[12.5px] leading-relaxed" aria-hidden="true">
                        <p class="text-white/35"># así viaja tu correo</p>
                        <p><span class="text-white/40">Received:</span> <span class="text-white/70">from mail.{{ $mainDomain }}</span></p>
                        <p class="pl-4 text-white/70">(using <span class="text-amarillo">TLS</span> with cipher <span class="text-amarillo">ECDHE</span>)</p>
                        <p><span class="text-white/40">Authentication-Results:</span> <span class="text-green-400">spf=pass dkim=pass dmarc=pass</span></p>
                        <p><span class="text-white/40">X-Spam-Status:</span> <span class="text-green-400">No</span><span class="text-white/40">, revisado por Rspamd</span></p>
                        <p><span class="text-white/40">X-Anuncios:</span> <span class="text-white/70">ninguno</span><span class="hc-caret"></span></p>
                    </div>
                </div>
                <div class="hc-reveal hc-card hc-ring hc-spot relative overflow-hidden p-7 md:col-span-2">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-white/[0.06] text-amarillo"><x-icon.eye-off class="size-5" /></div>
                    <h3 class="mt-5 text-lg font-bold">{{ $blocks[2]['title'] }}</h3>
                    <p class="mt-2 text-[15px] text-white/55">Ni anuncios en tu bandeja ni perfiles con lo que lees. El webmail bloquea las imágenes espía.</p>
                </div>
                <div class="hc-reveal hc-card hc-ring hc-spot relative overflow-hidden p-7 md:col-span-2">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-white/[0.06] text-amarillo"><x-icon.flag class="size-5" /></div>
                    <h3 class="mt-5 text-lg font-bold">{{ $blocks[0]['title'] }}</h3>
                    <p class="mt-2 text-[15px] text-white/55">Servicio español, servidores en la UE y bajo el RGPD.</p>
                </div>
                @foreach ([3, 4, 5] as $i)
                    <div class="hc-reveal hc-card hc-ring hc-spot relative overflow-hidden p-7 md:col-span-2" style="transition-delay: {{ ($loop->index) * 80 }}ms">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-white/[0.06] text-amarillo"><x-dynamic-component :component="'icon.'.$blocks[$i]['icon']" class="size-5" /></div>
                        <h3 class="mt-5 text-lg font-bold">{{ $blocks[$i]['title'] }}</h3>
                        <p class="mt-2 text-[15px] text-white/55">{{ $blocks[$i]['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- INFRAESTRUCTURA: lo que el socio vio en el back, a la vista --}}
    <section id="infraestructura" class="relative scroll-mt-16 border-y border-white/[0.06] bg-[#0e0d0c] px-4 py-20 sm:px-6 sm:py-28">
        <div class="hc-grid absolute inset-0 opacity-60" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl items-center gap-14 lg:grid-cols-2">
            <div class="hc-reveal">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-amarillo">Infraestructura</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-[-0.03em] sm:text-5xl">Un servidor propio, cuidado al detalle.</h2>
                <p class="mt-5 text-lg text-white/55 text-pretty">No revendemos el correo de otro. Montamos y vigilamos cada pieza: entrega, antispam, cifrado y copias. Y lo publicamos.</p>

                <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-white/[0.07] bg-white/[0.07]">
                    @foreach ([
                        ['10/10', 'en mail-tester'],
                        ['10 min', 'entre cada revisión'],
                        ['2 copias', 'diarias, una fuera del servidor'],
                        ['35 MB', 'por mensaje, como Gmail'],
                    ] as [$value, $label])
                        <div class="bg-[#0e0d0c] p-6">
                            <dt class="sr-only">{{ $label }}</dt>
                            <dd class="font-mono text-3xl font-semibold tracking-tight text-white">{{ $value }}</dd>
                            <dd class="mt-1 text-sm text-white/45">{{ $label }}</dd>
                        </div>
                    @endforeach
                </dl>
                <a href="{{ route('status') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-white/80 transition hover:text-amarillo">Ver el estado del servicio en directo <span aria-hidden="true">→</span></a>
            </div>

            {{-- Consola: comprobaciones del monitor --}}
            <div class="hc-reveal hc-ring relative overflow-hidden rounded-2xl bg-black/60 shadow-2xl shadow-black/60"
                 x-data="{ shown: 0, lines: 9 }"
                 x-init="new IntersectionObserver(([e], o) => { if (e.isIntersecting) { o.disconnect(); let t = setInterval(() => { shown++; if (shown >= lines) clearInterval(t) }, 260) } }).observe($el)">
                <div class="flex items-center justify-between border-b border-white/[0.07] px-4 py-3 font-mono text-xs text-white/40">
                    <span>mail-monitor</span><span>cada 10 min</span>
                </div>
                <div class="space-y-1.5 p-5 font-mono text-[13px]" aria-label="Comprobaciones automáticas del servidor">
                    @foreach ([
                        ['Postfix · entrega SMTP', 'ok'],
                        ['Dovecot · IMAP cifrado (993)', 'ok'],
                        ['Rspamd · antispam y firma DKIM', 'ok'],
                        ['Certificados TLS', 'vigentes'],
                        ['SPF · DKIM · DMARC', 'publicados'],
                        ['Listas negras (IP y dominio)', 'limpio'],
                        ['Copia de seguridad', '< 26 h'],
                        ['Copia externa', '< 36 h'],
                        ['Disco', '< 85 %'],
                    ] as [$check, $state])
                        <p class="flex items-center justify-between gap-4 transition-all duration-500"
                           :class="shown > {{ $loop->index }} ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2'">
                            <span class="text-white/70"><span class="text-white/25">$</span> {{ $check }}</span>
                            <span class="flex items-center gap-2 text-green-400"><span class="size-1.5 rounded-full bg-green-400"></span>{{ $state }}</span>
                        </p>
                    @endforeach
                    <p class="pt-3 text-white/35" x-show="shown >= lines" x-transition.opacity>→ si algo falla, nos avisa al móvil al momento<span class="hc-caret"></span></p>
                </div>
            </div>
        </div>
    </section>

    {{-- CÓMO FUNCIONA --}}
    @php $preview = config('landing.preview'); @endphp
    <section class="px-4 py-20 sm:px-6 sm:py-28">
        <div class="mx-auto max-w-6xl">
            <div class="hc-reveal text-center">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-amarillo">{{ $preview['steps_title'] }}</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-[-0.03em] sm:text-5xl">Tres pasos. Un minuto.</h2>
            </div>
            <ol class="relative mt-16 grid gap-4 md:grid-cols-3">
                <div class="absolute left-[16%] right-[16%] top-7 hidden h-px bg-gradient-to-r from-transparent via-white/20 to-transparent md:block" aria-hidden="true"></div>
                @foreach ($preview['steps'] as $step)
                    <li class="hc-reveal relative text-center" style="transition-delay: {{ $loop->index * 120 }}ms">
                        <span class="hc-ring relative mx-auto flex size-14 items-center justify-center rounded-2xl bg-[#141210] font-mono text-lg text-amarillo">0{{ $loop->iteration }}</span>
                        <h3 class="mt-6 text-lg font-bold">{{ $step['title'] }}</h3>
                        <p class="mx-auto mt-2 max-w-xs text-white/55">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>

            {{-- O pídele ayuda a tu IA: lo que ya hace la guía de configuración (x-ai-help), enseñado aquí --}}
            @php
                $helpers = config('mail_clients.ai_helpers');
                $c = config('mail_clients');
            @endphp
            <div class="hc-reveal hc-ring relative mt-20 overflow-hidden rounded-3xl bg-gradient-to-br from-amarillo/[0.12] via-[#141210] to-rojo/[0.14] p-6 sm:p-10">
                <div class="grid items-center gap-10 lg:grid-cols-2">
                    <div>
                        <p class="font-mono text-xs uppercase tracking-[0.25em] text-amarillo">¿Te atascas?</p>
                        <h3 class="mt-3 text-3xl font-extrabold tracking-[-0.03em] sm:text-4xl">O pídele ayuda a tu IA para configurarlo.</h3>
                        <p class="mt-4 text-white/60 text-pretty">
                            En la guía de tu app, eliges tu asistente favorito y se abre con tus datos de configuración ya escritos. Te guía paso a paso.
                            <strong class="font-semibold text-white">Nunca le damos tu contraseña.</strong>
                        </p>
                        <ul class="mt-7 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($helpers as $key => $ai)
                                @php $color = $key === 'chatgpt' ? '#ffffff' : $ai['color']; @endphp
                                <li class="flex min-w-0 items-center gap-2.5 rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm font-semibold"
                                    style="box-shadow: inset 0 0 0 1px {{ $color }}14">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-lg" style="background-color: {{ $color }}1f; color: {{ $color }}">
                                        <span class="size-4 [&>svg]:size-full [&>svg]:fill-current" aria-hidden="true">{!! file_get_contents(resource_path("svg/ai/{$ai['icon']}.svg")) !!}</span>
                                    </span>
                                    <span class="min-w-0 truncate">{{ $ai['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Así le llega al asistente (ejemplo, con la dirección del visitante si ya la ha elegido) --}}
                    <div class="ph-no-capture rounded-2xl border border-white/[0.08] bg-black/50 p-5 shadow-2xl shadow-black/40"
                         x-data="{ get address() { return $store.signup.email || @js('tunombre@'.$mainDomain) } }" aria-hidden="true">
                        <div class="flex items-center gap-2 text-xs text-white/40">
                            <span class="grid size-6 place-items-center rounded-md" style="background-color: {{ $helpers['claude']['color'] ?? '#D97757' }}26; color: {{ $helpers['claude']['color'] ?? '#D97757' }}">
                                <span class="size-3.5 [&>svg]:size-full [&>svg]:fill-current">{!! file_get_contents(resource_path('svg/ai/claude.svg')) !!}</span>
                            </span>
                            Nuevo chat
                        </div>
                        <div class="ml-auto mt-4 max-w-[92%] rounded-2xl rounded-br-md bg-white/[0.07] p-4 text-[13.5px] leading-relaxed text-white/75">
                            <p>Hola. Necesito que me ayudes a configurar mi cuenta de correo en el iPhone. Guíame paso a paso…</p>
                            <ul class="mt-3 space-y-1 font-mono text-[12px] text-white/55">
                                <li>· Usuario: <span class="text-amarillo" x-text="address" data-ph-mask></span></li>
                                <li>· IMAP: {{ $c['host'] }}, puerto {{ $c['imap']['port'] }}, SSL/TLS</li>
                                <li>· SMTP: {{ $c['host'] }}, puerto {{ $c['smtp']['port'] }}, SSL/TLS</li>
                                <li>· Contraseña: <span class="text-green-400">no te la doy, la pego yo en la app</span></li>
                            </ul>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full" style="background-color: {{ $helpers['claude']['color'] ?? '#D97757' }}26; color: {{ $helpers['claude']['color'] ?? '#D97757' }}">
                                <span class="size-3.5 [&>svg]:size-full [&>svg]:fill-current">{!! file_get_contents(resource_path('svg/ai/claude.svg')) !!}</span>
                            </span>
                            <p class="text-[13.5px] leading-relaxed text-white/60">¡Vamos allá! Primero, abre <span class="text-white">Ajustes → Apps → Mail → Cuentas</span> y dime cuando lo tengas<span class="hc-caret"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- PLANES (misma lógica que la home) --}}
    @php
        $groups = PlanGroups::of($plans);
        $period = $groups->mapWithKeys(fn ($g) => [$g['key'] => $g['default']['interval'] === 'year' ? 'year' : 'month']);
    @endphp
    <section id="planes" class="relative scroll-mt-16 px-4 py-20 sm:px-6 sm:py-28"
             x-data="{ period: @js($period), setPeriod(group, yearly) { this.period[group] = yearly ? 'year' : 'month' } }">
        <div class="hc-aurora r left-1/2 top-40 h-80 w-[40rem] -translate-x-1/2 opacity-20" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl">
            <div class="hc-reveal mx-auto max-w-2xl text-center">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-amarillo">Precios</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-[-0.03em] sm:text-5xl">{{ config('landing.plans.title') }}</h2>
                <p class="mt-4 text-lg text-white/55">{{ config('landing.plans.subtitle') }}</p>
                <p x-cloak x-show="$store.signup.email" data-ph-mask class="mt-6 inline-flex max-w-full items-center gap-2 rounded-full border border-green-400/25 bg-green-400/10 px-4 py-1.5 text-green-100">
                    <x-icon.check class="size-4 shrink-0 text-green-400" />
                    <span class="min-w-0">Tu dirección: <strong class="break-all font-mono font-medium" x-text="$store.signup.email"></strong></span>
                </p>
            </div>

            @if ($groups->isEmpty())
                <p class="mt-12 text-center text-white/50">Muy pronto publicaremos los planes.</p>
            @else
                <div @class([
                    'mx-auto mt-16 grid items-stretch gap-4',
                    'max-w-md' => $groups->count() === 1,
                    'max-w-4xl md:grid-cols-2' => $groups->count() === 2,
                    'md:grid-cols-3' => $groups->count() >= 3,
                ])>
                    @foreach ($groups as $group)
                        @php
                            $highlighted = $group['plans']->contains('is_highlighted', true);
                            $twinned = $group['plans']->count() > 1;
                        @endphp
                        <article @class([
                            'hc-reveal hc-ring hc-spot relative flex flex-col rounded-3xl p-8',
                            'is-hot bg-gradient-to-b from-[#2a1512] to-[#141210] shadow-[0_30px_80px_-20px_rgb(170_21_27/0.5)]' => $highlighted,
                            'hc-card' => ! $highlighted,
                        ]) style="transition-delay: {{ $loop->index * 100 }}ms">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-semibold">{{ $group['name'] }}</h3>
                                @if ($highlighted)
                                    <span class="rounded-full bg-amarillo px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-tinta">El más elegido</span>
                                @endif
                            </div>
                            @if ($twinned)
                                <div class="mt-4 flex" role="radiogroup" aria-label="Forma de pago">
                                    <div class="inline-flex rounded-full bg-black/40 p-1 text-xs font-semibold ring-1 ring-white/10">
                                        <button type="button" role="radio" :aria-checked="period[@js($group['key'])] === 'year'" @click.prevent.stop="setPeriod(@js($group['key']), true)"
                                                :class="period[@js($group['key'])] === 'year' ? 'bg-white text-tinta' : 'text-white/60 hover:text-white'"
                                                class="rounded-full px-3 py-1.5 transition">Anual <span class="ml-1 text-[0.7rem] opacity-70">2 meses gratis</span></button>
                                        <button type="button" role="radio" :aria-checked="period[@js($group['key'])] !== 'year'" @click.prevent.stop="setPeriod(@js($group['key']), false)"
                                                :class="period[@js($group['key'])] !== 'year' ? 'bg-white text-tinta' : 'text-white/60 hover:text-white'"
                                                class="rounded-full px-3 py-1.5 transition">Mensual</button>
                                    </div>
                                </div>
                            @elseif ($group['default']['interval'] === 'year')
                                <p class="mt-4"><span class="inline-flex rounded-full bg-black/40 p-1 text-xs font-semibold ring-1 ring-white/10"><span class="rounded-full px-3 py-1.5 text-white/60">Solo pago anual</span></span></p>
                            @endif

                            @foreach ($group['plans'] as $plan)
                                @php
                                    $yearly = $plan['interval'] === 'year';
                                    $per = $yearly ? ' al año' : '/mes';
                                    $hasOffer = $plan['offer'] !== null;
                                @endphp
                                <div @if ($twinned) x-show="period[@js($group['key'])] === @js($yearly ? 'year' : 'month')" @if ($plan['slug'] !== $group['default']['slug']) x-cloak @endif @endif
                                     class="flex flex-1 flex-col">
                                    <div class="mt-8">
                                        @if ($plan['is_free'])
                                            <p class="text-5xl font-semibold tracking-tight">Gratis</p>
                                        @else
                                            @if ($hasOffer)
                                                <p class="text-sm text-white/45">
                                                    <del aria-label="Antes">{{ Money::format(PlanGroups::perMonth($plan, $plan['price_cents'])) }}/mes</del>
                                                    <span class="ml-1 rounded-full bg-amarillo/20 px-2 py-0.5 text-xs font-bold text-amarillo">{{ $plan['offer']->label }}</span>
                                                </p>
                                            @endif
                                            <p class="text-5xl font-semibold tracking-[-0.03em]">
                                                {{ Money::format(PlanGroups::perMonth($plan, $plan['effective_price_cents'])) }}<span class="ml-1 text-base font-normal tracking-normal text-white/45">/mes</span>
                                            </p>
                                            @if ($yearly)
                                                <p class="mt-2 text-sm text-white/45">Pago anual de {{ Money::format($plan['effective_price_cents']) }}.</p>
                                            @endif
                                            @if ($hasOffer && $plan['offer']->duration === 'repeating' && $plan['offer']->duration_months)
                                                <p class="mt-1 text-sm text-white/45">Los primeros {{ $plan['offer']->duration_months }} meses; después, {{ Money::format($plan['price_cents']) }}{{ $per }}.</p>
                                            @elseif ($hasOffer && $plan['offer']->duration === 'once')
                                                <p class="mt-1 text-sm text-white/45">El primer pago; después, {{ Money::format($plan['price_cents']) }}{{ $per }}.</p>
                                            @endif
                                        @endif
                                    </div>
                                    @if ($plan['description'])
                                        <p class="mt-4 text-[15px] text-white/55">{{ $plan['description'] }}</p>
                                    @endif

                                    <div class="mt-6">
                                        @if ($plan['is_free'])
                                            <p x-cloak x-show="$store.signup.needsPaidPlan" class="mb-3 text-sm text-white/45">Los nombres cortos necesitan un plan de pago.</p>
                                        @endif
                                        <a href="{{ route('signup', ['plan' => $plan['slug']]) }}"
                                           data-plan="{{ $plan['slug'] }}" data-free="{{ $plan['is_free'] ? 1 : 0 }}" data-offer="{{ $hasOffer ? 1 : 0 }}"
                                           :href="'{{ route('signup') }}?' + new URLSearchParams({ plan: @js($plan['slug']), ...($store.signup.email ? { nombre: $store.signup.check.local_part, dominio: $store.signup.check.domain } : {}) })"
                                           @if ($plan['is_free']) :class="$store.signup.needsPaidPlan && 'pointer-events-none opacity-40'" :aria-disabled="$store.signup.needsPaidPlan" @endif
                                           @class([
                                               'block rounded-xl px-6 py-3 text-center font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amarillo',
                                               'bg-white text-tinta hover:bg-amarillo' => $highlighted,
                                               'bg-white/[0.07] text-white ring-1 ring-white/10 hover:bg-white/[0.12]' => ! $highlighted,
                                           ])>
                                            {{ $plan['is_free'] ? 'Empezar gratis' : 'Elegir '.$group['name'] }}
                                        </a>
                                    </div>

                                    @if ($plan['features'])
                                        <ul class="mt-8 space-y-3 border-t border-white/[0.07] pt-6 text-[15px] text-white/70">
                                            @foreach ($plan['features'] as $feature)
                                                <li class="flex gap-3"><x-icon.check class="mt-0.5 size-4 shrink-0 text-amarillo" /><span>{{ $feature }}</span></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </div>
                <p class="mt-8 text-center text-sm text-white/40">Pago seguro con Stripe · 14 días de desistimiento · Cancela desde tu cuenta</p>
            @endif
        </div>
    </section>

    {{-- PASARTE ES FÁCIL + PREGUNTAS, en dos columnas --}}
    @php $switch = config('landing.switch'); @endphp
    <section id="faq" class="scroll-mt-16 border-t border-white/[0.06] px-4 py-20 sm:px-6 sm:py-28">
        <div class="mx-auto grid max-w-6xl gap-16 lg:grid-cols-[1fr_1.4fr]">
            <div class="hc-reveal">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-amarillo">Mudanza</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-[-0.03em]">{{ $switch['title'] }}</h2>
                <p class="mt-4 text-white/55 text-pretty">{{ $switch['subtitle'] }}</p>
                <ol class="mt-8 space-y-5">
                    @foreach ($switch['items'] as $item)
                        <li class="flex gap-4">
                            <span class="mt-0.5 shrink-0 font-mono text-sm text-amarillo">0{{ $loop->iteration }}</span>
                            <div>
                                <h3 class="font-semibold">{{ $item['title'] }}</h3>
                                <p class="mt-1 text-[15px] text-white/50">{{ $item['text'] }}</p>
                                @foreach ($item['guides'] ?? [] as $guide)
                                    <details class="group mt-3 rounded-xl border border-white/[0.07] bg-white/[0.03] px-4 py-2.5">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-medium">
                                            {{ $guide['name'] }}
                                            <span class="text-white/40 transition group-open:rotate-45" aria-hidden="true">+</span>
                                        </summary>
                                        <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-white/55">
                                            @foreach ($guide['steps'] as $step)
                                                <li>{{ $step }}</li>
                                            @endforeach
                                        </ol>
                                    </details>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="hc-reveal">
                <h2 class="text-2xl font-bold tracking-tight">Preguntas frecuentes</h2>
                <div class="mt-6 divide-y divide-white/[0.07] border-y border-white/[0.07]">
                    @foreach (config('landing.faq') as $item)
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-white/90 transition hover:text-white">
                                {{ $item['q'] }}
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full border border-white/15 text-white/50 transition group-open:rotate-45 group-open:border-amarillo group-open:text-amarillo" aria-hidden="true">+</span>
                            </summary>
                            <p class="mt-3 pr-10 text-[15px] leading-relaxed text-white/55">{{ $item['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="px-4 pb-24 sm:px-6">
        <div class="hc-reveal hc-ring relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-[#141210] px-6 py-20 text-center sm:px-12">
            <div class="hc-aurora r -left-20 -top-20 h-72 w-72" aria-hidden="true"></div>
            <div class="hc-aurora a left-1/2 top-1/2 h-60 w-96 -translate-x-1/2" aria-hidden="true"></div>
            <div class="hc-aurora r -bottom-20 -right-20 h-72 w-72" aria-hidden="true"></div>
            <div class="relative">
                <h2 class="text-4xl font-extrabold tracking-[-0.03em] text-balance sm:text-6xl">Tu nombre te está esperando.</h2>
                <p class="mx-auto mt-5 max-w-lg text-lg text-white/60">Elígelo antes de que lo haga otro. Desde 0,42 € al mes.</p>
                <a href="#top" @click.prevent="window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(() => document.getElementById('nombre')?.focus({ preventScroll: true }), 500)"
                   class="mt-10 inline-flex items-center gap-2 rounded-xl bg-white px-7 py-4 text-base font-semibold text-tinta transition hover:bg-amarillo">
                    Elegir mi dirección <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- PIE --}}
    <footer class="border-t border-white/[0.06] px-4 py-14 sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-col gap-10 md:flex-row md:items-start md:justify-between">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold tracking-tight">
                    <span class="flex h-5 w-5 flex-col overflow-hidden rounded-[5px] ring-1 ring-white/20" aria-hidden="true">
                        <span class="h-1/4 bg-rojo"></span><span class="h-1/2 bg-amarillo"></span><span class="h-1/4 bg-rojo"></span>
                    </span>
                    <span>unagrandeylibre<span class="text-white/40">.es</span></span>
                </a>
                <p class="mt-3 max-w-xs text-sm text-white/45">Correo hecho en España.</p>
                <a href="{{ route('status') }}" class="mt-5 inline-flex items-center gap-2 rounded-full border border-white/10 px-3 py-1 text-xs text-white/55 transition hover:text-white">
                    <span class="size-1.5 rounded-full bg-green-400"></span> Estado del servicio
                </a>
            </div>
            <nav aria-label="Información legal">
                <ul class="grid gap-x-12 gap-y-2.5 text-sm text-white/50 sm:grid-cols-2">
                    @foreach (config('landing.legal') as $slug => $label)
                        <li><a href="{{ route('legal', $slug) }}" class="transition hover:text-white">{{ $label }}</a></li>
                    @endforeach
                    <li><a href="{{ route('support') }}" class="font-medium text-white/80 transition hover:text-white">Ayuda y contacto</a></li>
                    <li><button type="button" x-data @click="$dispatch('ugl-open-consent')" class="transition hover:text-white">Configurar cookies</button></li>
                </ul>
            </nav>
        </div>
        <p class="mx-auto mt-12 max-w-6xl font-mono text-xs text-white/30">© {{ now()->year }} unagrandeylibre.es</p>
    </footer>
</div>
</x-layouts.site>
