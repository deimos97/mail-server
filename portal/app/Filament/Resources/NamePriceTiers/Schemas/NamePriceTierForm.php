<?php

namespace App\Filament\Resources\NamePriceTiers\Schemas;

use App\Filament\Support\Fields;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NamePriceTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('min_length')->label('Desde (caracteres)')->numeric()->minValue(1)->maxValue(64)->required(),
                TextInput::make('max_length')->label('Hasta (caracteres)')->numeric()->minValue(1)->maxValue(64)->required()->gte('min_length'),
                Fields::euros('price_cents')
                    ->label('Sobrecoste al mes')
                    ->helperText('IVA incluido. Se suma al plan; solo se puede coger con planes de pago.')
                    ->required(),
                Toggle::make('is_active')->label('Activo')->default(true),
                TextInput::make('stripe_price_id')->label('Stripe Price ID')->disabled()->dehydrated(false)->helperText('Fase 4.'),
            ]);
    }
}
