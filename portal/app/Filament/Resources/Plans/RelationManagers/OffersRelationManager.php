<?php

namespace App\Filament\Resources\Plans\RelationManagers;

use App\Filament\Support\Fields;
use App\Models\PlanOffer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Ofertas';

    protected static ?string $modelLabel = 'oferta';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('label')
                    ->label('Etiqueta')
                    ->helperText('Se muestra en la tarjeta: "-50 % lanzamiento", "hasta el 31/12"…')
                    ->required()
                    ->maxLength(40)
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Tipo')
                    ->options(['percent' => 'Porcentaje', 'amount' => 'Importe fijo'])
                    ->default('percent')
                    ->required()
                    ->live(),
                TextInput::make('value')
                    ->label('Descuento')
                    ->numeric()
                    ->required()
                    ->minValue(fn (Get $get) => $get('type') === 'percent' ? 1 : 0.01)
                    ->maxValue(fn (Get $get) => $get('type') === 'percent' ? 100 : null)
                    ->step(fn (Get $get) => $get('type') === 'percent' ? 1 : 0.01)
                    ->suffix(fn (Get $get) => $get('type') === 'percent' ? '%' : '€')
                    // Porcentaje tal cual; importe en euros que se guarda en céntimos
                    ->formatStateUsing(fn ($state, Get $get) => $state !== null && $get('type') === 'amount' ? number_format($state / 100, 2, '.', '') : $state)
                    ->dehydrateStateUsing(fn ($state, Get $get) => $get('type') === 'amount' ? (int) round(((float) $state) * 100) : (int) $state),
                Select::make('duration')
                    ->label('Duración del descuento para quien lo contrata')
                    ->options(['once' => 'Solo el primer pago', 'repeating' => 'Varios meses', 'forever' => 'Para siempre'])
                    ->default('once')
                    ->required()
                    ->live(),
                TextInput::make('duration_months')
                    ->label('Meses')
                    ->numeric()
                    ->minValue(1)
                    ->required(fn (Get $get) => $get('duration') === 'repeating')
                    ->visible(fn (Get $get) => $get('duration') === 'repeating'),
                DateTimePicker::make('starts_at')->label('Desde')->seconds(false)->helperText('Vacío: desde ya.'),
                DateTimePicker::make('ends_at')->label('Hasta')->seconds(false)->after('starts_at')->helperText('Vacío: sin fin.'),
                TextInput::make('max_redemptions')->label('Máximo de usos')->numeric()->minValue(1)->helperText('Vacío: sin límite.'),
                Toggle::make('is_active')->label('Activa')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('label')->label('Etiqueta'),
                TextColumn::make('value')
                    ->label('Descuento')
                    ->formatStateUsing(fn (PlanOffer $record) => $record->type === 'percent' ? "-{$record->value} %" : '-'.Fields::formatEuros($record->value)),
                TextColumn::make('result')
                    ->label('Precio con oferta')
                    ->state(fn (PlanOffer $record) => Fields::formatEuros($record->apply($this->getOwnerRecord()->price_cents))),
                TextColumn::make('starts_at')->label('Desde')->dateTime('d/m/Y H:i')->placeholder('Ya'),
                TextColumn::make('ends_at')->label('Hasta')->dateTime('d/m/Y H:i')->placeholder('Sin fin'),
                IconColumn::make('running')->label('En curso')->state(fn (PlanOffer $record) => $record->isRunning())->boolean(),
                ToggleColumn::make('is_active')->label('Activa'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
