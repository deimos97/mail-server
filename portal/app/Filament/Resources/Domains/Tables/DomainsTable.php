<?php

namespace App\Filament\Resources\Domains\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Dominio')->searchable(),
                IconColumn::make('active')->label('Activo (correo)')->boolean(),
                ToggleColumn::make('public_signup')->label('En el alta'),
                TextColumn::make('sort_order')->label('Orden'),
                TextColumn::make('created_at')->label('Creado')->date('d/m/Y'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
