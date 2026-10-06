<?php

namespace App\Filament\Resources\NamePriceTiers\Tables;

use App\Filament\Support\Fields;
use App\Models\NamePriceTier;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class NamePriceTiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('min_length')
            ->columns([
                TextColumn::make('range')
                    ->label('Longitud')
                    ->state(fn (NamePriceTier $record) => $record->min_length === $record->max_length
                        ? "{$record->min_length} caracteres"
                        : "{$record->min_length}–{$record->max_length} caracteres"),
                TextColumn::make('price_cents')->label('Sobrecoste')->formatStateUsing(fn ($state) => Fields::formatEuros($state).'/mes'),
                ToggleColumn::make('is_active')->label('Activo'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
