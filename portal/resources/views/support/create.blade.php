@php($limits = config('support.attachments'))
<x-signup-layout title="Ayuda y contacto">
    <p class="mt-2 text-stone-600">Cuéntanos qué necesitas y te contestamos por email.</p>
    <p class="mt-2 text-sm text-stone-500">
        Antes, por si te sirve: <a href="{{ route('help.setup') }}" class="font-medium underline">guías para configurar el correo</a>
        y <a href="{{ route('status') }}" class="font-medium underline">estado del servicio</a>.
    </p>

    @if (! $user && ! $guestsAllowed)
        <div class="mt-6 rounded-2xl bg-stone-100 p-5">
            <p class="font-semibold">Para escribirnos, entra en tu cuenta.</p>
            <p class="mt-1 text-sm text-stone-600">Así sabemos de qué buzón nos hablas y te respondemos a tu email de recuperación.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="rounded-xl bg-rojo px-4 py-2 font-semibold text-white hover:bg-rojo-oscuro">Entrar</a>
                <a href="{{ route('password.request') }}" class="rounded-xl px-4 py-2 font-semibold ring-1 ring-stone-300 hover:bg-white">No recuerdo la contraseña</a>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5"
              x-data="supportForm({{ $limits['max_files'] }}, {{ $limits['max_total_kb'] * 1024 }})" @submit="sending = true">
            @csrf

            @if ($user)
                <p class="rounded-2xl bg-stone-100 px-4 py-3 text-sm text-stone-600">Te responderemos a <strong class="text-tinta" data-ph-mask>{{ $user->email }}</strong>, tu email de recuperación.</p>
            @else
                <div>
                    <label for="email" class="block font-semibold">Tu email</label>
                    <p class="text-sm text-stone-500">Te responderemos aquí.</p>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" maxlength="254"
                           class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    <x-form-error name="email" />
                </div>
            @endif

            <div>
                <label for="subject" class="block font-semibold">Asunto</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="150"
                       class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 text-lg outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                       @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
                <x-form-error name="subject" />
            </div>

            <div>
                <label for="body" class="block font-semibold">¿Qué pasa?</label>
                <p class="text-sm text-stone-500">Cuanto más detalle (qué app o dispositivo, qué mensaje de error), antes lo resolvemos.</p>
                <textarea id="body" name="body" rows="7" required maxlength="5000"
                          class="mt-2 w-full rounded-2xl bg-stone-100 px-4 py-3.5 outline-none ring-2 ring-transparent focus:bg-white focus:ring-rojo/60"
                          @error('body') aria-invalid="true" aria-describedby="body-error" @enderror>{{ old('body') }}</textarea>
                <x-form-error name="body" />
            </div>

            <div>
                <span class="block font-semibold">Capturas de pantalla <span class="font-normal text-stone-500">(opcional)</span></span>
                <p class="text-sm text-stone-500">
                    Solo imágenes, hasta {{ $limits['max_files'] }} y {{ $limits['max_total_kb'] / 1024 }} MB en total.
                    Para otros archivos o más pesados, súbelos a <a href="{{ config('support.transfer_url') }}" target="_blank" rel="noopener" class="font-medium underline">transfer.tibletech.com</a> y pega el enlace en el mensaje.
                </p>
                <label class="mt-2 flex cursor-pointer items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-stone-300 px-4 py-4 font-semibold text-stone-600 hover:border-rojo/50 hover:text-rojo">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/></svg>
                    <span x-text="files.length ? 'Cambiar imágenes' : 'Elegir imágenes'">Elegir imágenes</span>
                    <input type="file" name="images[]" multiple accept="image/*,.heic,.heif" class="sr-only" x-ref="input" @change="pick($event)">
                </label>
                <ul class="mt-2 space-y-1 text-sm text-stone-600" x-show="files.length" x-cloak>
                    <template x-for="file in files" :key="file.name">
                        <li class="flex justify-between gap-3"><span class="min-w-0 truncate" x-text="file.name"></span><span class="shrink-0 text-stone-500" x-text="mb(file.size)"></span></li>
                    </template>
                </ul>
                <p class="mt-2 text-sm font-medium text-rojo" role="alert" x-show="error" x-text="error" x-cloak></p>
                <x-form-error name="images" />
                @foreach ($errors->get('images.*') as $messages)
                    <p class="mt-2 text-sm font-medium text-rojo" role="alert">{{ $messages[0] }}</p>
                    @break
                @endforeach
            </div>

            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="es" data-theme="light" data-size="flexible"></div>
            <x-form-error name="turnstile" />

            <button type="submit" class="w-full rounded-2xl bg-rojo px-6 py-4 text-lg font-bold text-white transition hover:bg-rojo-oscuro disabled:opacity-60" :disabled="sending">
                <span x-text="sending ? 'Enviando…' : 'Enviar'">Enviar</span>
            </button>
        </form>

        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</x-signup-layout>
