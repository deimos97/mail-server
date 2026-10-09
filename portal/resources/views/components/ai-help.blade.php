@props(['mailbox', 'client'])
@php
    $prompt = \App\Support\MailClients\AiHelpPrompt::make($mailbox, $client);
    $helpers = config('mail_clients.ai_helpers');
@endphp

{{-- "Que te ayude una IA": abre el chat con las instrucciones (sin la contraseña) y las copia por si el chat no las recibe --}}
<section class="mt-8 overflow-hidden rounded-3xl bg-linear-to-br from-amarillo/30 via-white to-rojo/10 p-4 ring-1 ring-amarillo/60 sm:p-6"
         x-data="{ copied: null, prompt: @js($prompt) }">
    <p class="text-xs font-bold uppercase tracking-wider text-rojo">¿Te atascas?</p>
    <h2 class="mt-1 text-2xl font-extrabold tracking-tight">Que te ayude una IA</h2>
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
