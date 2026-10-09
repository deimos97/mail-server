<footer class="border-t border-stone-200 bg-white px-4 py-12 sm:px-6">
    <div class="mx-auto flex max-w-6xl flex-col gap-8 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight">unagrandeylibre<span class="text-rojo">.es</span></a>
            <p class="mt-2 max-w-xs text-sm text-stone-500">Correo hecho en España.</p>
            <div class="mt-4 flex h-1.5 w-24 overflow-hidden rounded-full" aria-hidden="true">
                <span class="w-1/4 bg-rojo"></span><span class="w-1/2 bg-amarillo"></span><span class="w-1/4 bg-rojo"></span>
            </div>
        </div>
        <nav aria-label="Información legal">
            <ul class="grid gap-x-10 gap-y-2 text-sm text-stone-600 sm:grid-cols-2">
                @foreach (config('landing.legal') as $slug => $label)
                    <li><a href="{{ route('legal', $slug) }}" class="hover:text-tinta hover:underline">{{ $label }}</a></li>
                @endforeach
                <li><a href="{{ route('status') }}" class="hover:text-tinta hover:underline">Estado del servicio</a></li>
                <li><button type="button" x-data @click="$dispatch('ugl-open-consent')" class="hover:text-tinta hover:underline">Configurar cookies</button></li>
            </ul>
        </nav>
    </div>
    <p class="mx-auto mt-10 max-w-6xl text-xs text-stone-500">© {{ now()->year }} unagrandeylibre.es</p>
</footer>
