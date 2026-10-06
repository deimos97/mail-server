<?php

namespace App\Filament\Resources\ReservedNames\Tables;

use App\Filament\Resources\ReservedNames\Schemas\ReservedNameForm;
use App\Filament\Support\Fields;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservedNamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('local_part')
            ->columns([
                TextColumn::make('local_part')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ReservedNameForm::reasons()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'sistema' => 'gray', 'marca' => 'info', 'ofensivo' => 'danger', 'premium' => 'warning', default => null,
                    }),
                TextColumn::make('price_cents')->label('Precio tienda')->formatStateUsing(fn ($state) => Fields::formatEuros($state))->placeholder('—'),
                TextColumn::make('notes')->label('Notas')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('reason')->label('Motivo')->options(ReservedNameForm::reasons()),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
