<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Filament\Support\Fields;
use App\Models\Plan;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('offers'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Plan')->searchable()->description(fn (Plan $record) => $record->slug),
                TextColumn::make('price_cents')
                    ->label('Precio')
                    ->formatStateUsing(fn (Plan $record) => $record->is_free ? 'Gratis' : Fields::formatEuros($record->price_cents).($record->interval === 'year' ? '/año' : '/mes')),
                TextColumn::make('effective_price')
                    ->label('Ahora')
                    ->state(fn (Plan $record) => $record->is_free ? 'Gratis' : Fields::formatEuros($record->effectivePriceCents()))
                    ->description(fn (Plan $record) => $record->currentOffer()?->label)
                    ->color(fn (Plan $record) => $record->currentOffer() ? 'success' : null),
                IconColumn::make('visible_now')
                    ->label('Visible ahora')
                    ->state(fn (Plan $record) => $record->isVisible())
                    ->boolean(),
                ToggleColumn::make('is_active')->label('Activo'),
                IconColumn::make('is_highlighted')->label('Destacado')->boolean(),
                TextColumn::make('quota_bytes')->label('Espacio')->formatStateUsing(fn ($state) => round($state / 1024 ** 3, 1).' GB'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Activo'),
                TernaryFilter::make('is_free')->label('Gratis'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
