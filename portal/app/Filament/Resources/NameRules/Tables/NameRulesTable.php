<?php

namespace App\Filament\Resources\NameRules\Tables;

use App\Models\NameRule;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NameRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('domain_id')->label('Ámbito')->formatStateUsing(fn () => 'Todos los dominios')->placeholder('Todos los dominios'),
                TextColumn::make('length')->label('Longitud')->state(fn (NameRule $record) => "{$record->min_length}–{$record->max_length}"),
                TextColumn::make('allowed_symbols')->label('Símbolos')->placeholder('ninguno'),
                IconColumn::make('forbid_edge_symbols')->label('Sin símbolos en los extremos')->boolean(),
                IconColumn::make('forbid_consecutive_symbols')->label('Sin símbolos seguidos')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
