<?php

namespace App\Filament\Resources\Experiments\Tables;

use App\Models\Experiment;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ExperimentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Prueba')->searchable()->description(fn (Experiment $record) => '$feature/'.$record->key),
                TextColumn::make('variants')
                    ->label('Variantes')
                    ->state(fn (Experiment $record) => collect($record->variants)->map(fn ($v) => $v['key'].' '.$v['weight'])->implode(' · ')),
                IconColumn::make('running')->label('En marcha ahora')->state(fn (Experiment $record) => $record->isRunning())->boolean(),
                ToggleColumn::make('is_active')->label('Activa'),
                TextColumn::make('starts_at')->label('Empieza')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('ends_at')->label('Termina')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
