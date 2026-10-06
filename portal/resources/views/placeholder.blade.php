<x-layouts.site :title="$title.' · Una Grande y Libre'">
    <main class="flex min-h-[70svh] items-center justify-center px-4 py-20 text-center">
        <div class="max-w-lg">
            <div class="mx-auto mb-8 flex h-2 w-32 overflow-hidden rounded-full" aria-hidden="true">
                <span class="w-1/4 bg-rojo"></span><span class="w-1/2 bg-amarillo"></span><span class="w-1/4 bg-rojo"></span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $title }}</h1>
            <p class="mt-4 text-lg text-stone-600">{{ $text }}</p>
            <a href="{{ route('home') }}" class="mt-8 inline-block rounded-2xl bg-rojo px-6 py-3 font-bold text-white hover:bg-rojo-oscuro">Volver al inicio</a>
        </div>
    </main>
    <x-site-footer />
</x-layouts.site>
