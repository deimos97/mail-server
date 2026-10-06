<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/** Campos reutilizables del admin. La BD guarda céntimos y bytes; el admin muestra € y GB. */
class Fields
{
    /** Importe en euros (IVA incluido) que se guarda en céntimos. */
    public static function euros(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->prefix('€')
            ->formatStateUsing(fn ($state) => $state === null ? null : number_format($state / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100));
    }

    /** Tamaño en GB que se guarda en bytes. */
    public static function gigabytes(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0.1)
            ->step(0.1)
            ->suffix('GB')
            ->formatStateUsing(fn ($state) => $state === null ? null : round($state / 1024 ** 3, 2))
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 1024 ** 3));
    }

    public static function formatEuros(?int $cents): string
    {
        return $cents === null ? '—' : number_format($cents / 100, 2, ',', '.').' €';
    }
}
