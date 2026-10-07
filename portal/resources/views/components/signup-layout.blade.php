@props(['step' => null, 'title', 'wide' => false])
@php $steps = ['Nombre', 'Cuenta', 'Plan', 'Confirmar']; @endphp

<x-layouts.site :title="$title.' · Una Grande y Libre'">
    <div class="flex h-1.5" aria-hidden="true"><span class="w-1/4 bg-rojo"></span><span class="w-1/2 bg-amarillo"></span><span class="w-1/4 bg-rojo"></span></div>

    <header @class(['mx-auto flex items-center justify-between px-4 pt-6 sm:px-6', 'max-w-xl' => ! $wide, 'max-w-2xl' => $wide])>
        <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight">unagrandeylibre<span class="text-rojo">.es</span></a>
        @auth
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="rounded-full px-3 py-1.5 text-sm font-medium text-stone-600 ring-1 ring-stone-300 hover:bg-white">Salir</button>
            </form>
        @endauth
    </header>

    <main @class(['mx-auto px-4 pb-16 pt-6 sm:px-6', 'max-w-xl' => ! $wide, 'max-w-2xl' => $wide])>
        @if ($step)
            <ol class="mb-6 grid grid-cols-4 gap-2" aria-label="Pasos del alta">
                @foreach ($steps as $i => $label)
                    @php $n = $i + 1; @endphp
                    <li @if ($n === $step) aria-current="step" @endif>
                        <span @class(['block h-1.5 rounded-full', 'bg-rojo' => $n <= $step, 'bg-stone-200' => $n > $step])></span>
                        <span @class(['mt-1.5 block text-xs font-medium', 'text-tinta' => $n === $step, 'text-stone-500' => $n !== $step])>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        @if (session('status'))
            <p class="mb-4 rounded-2xl bg-amarillo/20 px-4 py-3 text-sm font-medium" role="status">{{ session('status') }}</p>
        @endif

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $title }}</h1>
            {{ $slot }}
        </section>

        {{ $after ?? '' }}
    </main>
</x-layouts.site>
