@props(['mailbox', 'client'])
@php
    $prompt = \App\Support\MailClients\AiHelpPrompt::make($mailbox, $client);
    $helpers = config('mail_clients.ai_helpers');
@endphp

{{-- "Pídele ayuda a una IA": abre el chat con las instrucciones (sin la contraseña) y las copia por si el chat no las recibe --}}
<div x-data="{ seen: true }">
<section id="ayuda-ia" class="mt-8 scroll-mt-6 overflow-hidden rounded-3xl bg-linear-to-br from-amarillo/30 via-white to-rojo/10 p-4 ring-1 ring-amarillo/60 sm:p-6"
         x-data="{ copied: null, prompt: @js($prompt) }"
         x-init="new IntersectionObserver(([e]) => seen = e.isIntersecting, { threshold: 0.15 }).observe($el)">
    <p class="text-xs font-bold uppercase tracking-wider text-rojo">¿Te atascas?</p>
    <h2 class="mt-1 text-2xl font-extrabold tracking-tight">Pídele ayuda a una IA</h2>
    <p class="mt-2 text-sm text-stone-700">
        Elige tu asistente favorito: se abrirá con tus datos de configuración ya escritos y te guiará paso a paso.
        <strong>Nunca le damos tu contraseña</strong>: te dirá que la copies de esta página directamente en la app.
    </p>

    <div class="mt-4 grid grid-cols-2 gap-1.5 sm:gap-2">
        @foreach ($helpers as $key => $ai)
            @php($prefill = $ai['prefill'] ?? true)
            <a href="{{ $prefill ? str_replace('{q}', rawurlencode($prompt), $ai['url']) : $ai['url'] }}" target="_blank" rel="noopener noreferrer"
               class="flex min-w-0 items-center gap-2 rounded-2xl bg-white px-2 py-2 text-[13px] font-semibold shadow-sm ring-1 ring-stone-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-stone-300 sm:gap-3 sm:px-3 sm:text-base"
               @click="navigator.clipboard?.writeText(prompt).catch(() => {}); copied = @js($prefill ? null : $ai['label']); $dispatch('ugl:ai-help', { provider: @js($key) })">
                <span class="grid size-7 shrink-0 place-items-center rounded-lg sm:size-9 sm:rounded-xl" style="background-color: {{ $ai['color'] }}1a; color: {{ $ai['color'] }}">
                    <span class="size-4 sm:size-5 [&>svg]:size-full [&>svg]:fill-current" aria-hidden="true">{!! file_get_contents(resource_path("svg/ai/{$ai['icon']}.svg")) !!}</span>
                </span>
                <span class="min-w-0 leading-tight">{{ $ai['label'] }}</span>
            </a>
        @endforeach
    </div>

    <p x-show="copied" x-cloak class="mt-3 rounded-xl bg-white/80 p-3 text-sm" role="status">
        Hemos copiado las instrucciones. En <span x-text="copied"></span>, pégalas en el chat (mantén pulsado y «Pegar», o Ctrl/⌘ + V) y envíalas.
    </p>
    <details class="mt-3 text-sm text-stone-600">
        <summary class="cursor-pointer font-medium underline">Ver o copiar el texto que le mandamos</summary>
        <pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap rounded-xl bg-white/80 p-3 font-sans text-xs" data-ph-mask x-text="prompt"></pre>
        <button type="button" class="mt-2 rounded-xl bg-white px-3 py-1.5 font-semibold ring-1 ring-stone-300 hover:bg-stone-50"
                @click="navigator.clipboard.writeText(prompt); copied = 'tu asistente'">Copiar el texto</button>
    </details>
</section>

{{-- Aviso flotante mientras la sección no se ve: lleva a ella --}}
<button type="button" x-show="! seen" x-cloak
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-4 opacity-0"
        @click="document.getElementById('ayuda-ia').scrollIntoView({ behavior: 'smooth', block: 'start' })"
        class="fixed inset-x-4 z-40 mx-auto flex max-w-md items-center gap-3 rounded-2xl bg-white bg-linear-to-br from-amarillo/40 via-white to-rojo/15 p-3 text-left shadow-lg ring-1 ring-amarillo/70"
        style="bottom: calc(1rem + env(safe-area-inset-bottom))">
    <span class="flex shrink-0 -space-x-2" aria-hidden="true">
        @foreach (array_slice($helpers, 0, 3) as $ai)
            <span class="grid size-8 place-items-center rounded-full bg-white ring-2 ring-white" style="color: {{ $ai['color'] }}">
                <span class="size-4 [&>svg]:size-full [&>svg]:fill-current">{!! file_get_contents(resource_path("svg/ai/{$ai['icon']}.svg")) !!}</span>
            </span>
        @endforeach
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-[11px] font-bold uppercase tracking-wider text-rojo">¿Te atascas?</span>
        <span class="block font-extrabold leading-tight">Pídele ayuda a una IA</span>
    </span>
    <svg class="size-5 shrink-0 text-stone-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
</button>
</div>
