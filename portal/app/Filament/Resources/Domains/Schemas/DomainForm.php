<?php

namespace App\Filament\Resources\Domains\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Dominio')->disabled()->dehydrated(false),
                Toggle::make('public_signup')
                    ->label('Se ofrece en el alta')
                    ->helperText('Aparece en el selector @dominio de la landing.'),
                TextInput::make('sort_order')->label('Orden')->numeric()->minValue(0)->required(),
            ]);
    }
}
