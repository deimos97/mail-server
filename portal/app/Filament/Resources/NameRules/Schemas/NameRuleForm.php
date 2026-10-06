<?php

namespace App\Filament\Resources\NameRules\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NameRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('min_length')->label('Longitud mínima')->numeric()->minValue(1)->maxValue(64)->required()
                    ->helperText('Los nombres cortos con sobrecoste se controlan en "Tramos de precio".'),
                TextInput::make('max_length')->label('Longitud máxima')->numeric()->minValue(1)->maxValue(64)->required()->gte('min_length'),
                TextInput::make('allowed_symbols')
                    ->label('Símbolos permitidos')
                    ->helperText('Además de a-z y 0-9. Por ejemplo: ._-')
                    ->maxLength(16)
                    ->regex('/^[._\-+]*$/'),
                Toggle::make('forbid_edge_symbols')->label('Prohibir símbolos al principio o al final'),
                Toggle::make('forbid_consecutive_symbols')->label('Prohibir dos símbolos seguidos'),
            ]);
    }
}
