{{-- Estilos en línea: el panel usa el tema de Filament, que no compila las clases de Tailwind de estas vistas --}}
<x-filament-widgets::widget>
    <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));">
        @foreach ($lists as $title => $rows)
            <x-filament::section :heading="$title" compact>
                @forelse ($rows as $label => $n)
                    <div style="display: flex; justify-content: space-between; gap: .75rem; padding: .2rem 0; font-size: .875rem;">
                        <span style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; opacity: .8;" title="{{ $label }}">{{ $label }}</span>
                        <span style="flex-shrink: 0; font-weight: 600; font-variant-numeric: tabular-nums;">{{ number_format($n, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <p style="font-size: .875rem; opacity: .6;">Sin datos en el periodo.</p>
                @endforelse
            </x-filament::section>
        @endforeach
    </div>
</x-filament-widgets::widget>
