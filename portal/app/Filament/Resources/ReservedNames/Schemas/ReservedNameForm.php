<?php

namespace App\Filament\Resources\ReservedNames\Schemas;

use App\Filament\Support\Fields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ReservedNameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('local_part')
                    ->label('Nombre')
                    ->helperText('Sin @dominio. Se guarda en minúsculas.')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true),
                Select::make('reason')
                    ->label('Motivo')
                    ->options(self::reasons())
                    ->required()
                    ->live(),
                Fields::euros('price_cents')
                    ->label('Precio en la tienda')
                    ->helperText('Para la futura tienda de nombres. No se vende todavía.')
                    ->visible(fn (Get $get) => $get('reason') === 'premium'),
                TextInput::make('notes')->label('Notas')->maxLength(255)->columnSpanFull(),
            ]);
    }

    public static function reasons(): array
    {
        return ['sistema' => 'Sistema', 'marca' => 'Marca', 'ofensivo' => 'Ofensivo', 'premium' => 'Premium (tienda)'];
    }
}
