{{-- Banner de cookies. Aceptar y Rechazar con la misma relevancia (guía de cookies de la AEPD). --}}
<div x-data="consentBanner" x-cloak x-show="open" @ugl-open-consent.window="open = true"
     x-transition.opacity.duration.200ms
     class="fixed inset-x-0 bottom-0 z-50 p-3 sm:p-5"
     role="dialog" aria-modal="false" aria-labelledby="consent-title">
    <div class="mx-auto max-w-2xl rounded-3xl bg-tinta p-5 text-white shadow-2xl ring-1 ring-white/10 sm:p-6">
        <p id="consent-title" class="font-bold">Cookies</p>
        <p class="mt-2 text-sm text-white/80">
            Usamos cookies de análisis para saber cómo se usa la web y mejorarla (visitas, clics y grabaciones anónimas
            con los campos de texto ocultos). Solo se activan si aceptas.
            <a href="{{ route('legal', 'cookies') }}" class="underline hover:text-white">Más información sobre las cookies</a>.
        </p>
        <div class="mt-4 grid grid-cols-2 gap-3">
            <button type="button" @click="reject()"
                    class="rounded-2xl bg-white px-4 py-3 font-bold text-tinta transition hover:bg-stone-200 focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-amarillo">
                Rechazar
            </button>
            <button type="button" @click="accept()"
                    class="rounded-2xl bg-white px-4 py-3 font-bold text-tinta transition hover:bg-stone-200 focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-amarillo">
                Aceptar
            </button>
        </div>
    </div>
</div>
