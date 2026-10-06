<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Filament\Support\Fields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Plan')
                    ->description('Lo que se ve en la tarjeta de la landing.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(60)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                        TextInput::make('slug')
                            ->label('Identificador')
                            ->helperText('Interno y en URLs. No cambiarlo una vez haya clientes.')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(60),
                        Textarea::make('description')
                            ->label('Descripción corta')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TagsInput::make('features')
                            ->label('Características')
                            ->helperText('Una por línea en la tarjeta, en este orden. Escribe y pulsa Intro.')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Precio')
                    ->description('IVA incluido, en euros.')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_free')
                            ->label('Gratis')
                            ->live()
                            ->afterStateUpdated(fn (bool $state, Set $set) => $state ? $set('price_cents', 0) : null)
                            ->columnSpanFull(),
                        Fields::euros('price_cents')
                            ->label('Precio')
                            ->required(fn (Get $get) => ! $get('is_free'))
                            ->hidden(fn (Get $get) => $get('is_free')),
                        Select::make('interval')
                            ->label('Cada')
                            ->options(['month' => 'Mes', 'year' => 'Año'])
                            ->default('month')
                            ->required()
                            ->hidden(fn (Get $get) => $get('is_free')),
                    ]),

                Section::make('Buzón')
                    ->description('Lo que recibe cada buzón con este plan.')
                    ->columns(2)
                    ->schema([
                        Fields::gigabytes('quota_bytes')->label('Espacio')->required(),
                        TextInput::make('max_aliases')->label('Alias')->numeric()->minValue(0)->default(0)->required(),
                        TextInput::make('send_limit_per_hour')->label('Envíos por hora')->numeric()->minValue(1)->required(),
                        TextInput::make('tier')
                            ->label('Tier')
                            ->helperText('Se copia a mailboxes.tier (límites de Rspamd por plan).')
                            ->required()
                            ->alphaDash()
                            ->maxLength(16),
                    ]),

                Section::make('Visibilidad')
                    ->description('Cuándo se ofrece en la web. No afecta a quien ya lo tiene. Fechas en hora de Madrid.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('is_active')->label('Activo')->default(true),
                        Toggle::make('is_highlighted')->label('Destacado ("el más elegido")'),
                        DateTimePicker::make('available_from')->label('Visible desde')->seconds(false),
                        DateTimePicker::make('available_until')
                            ->label('Visible hasta')
                            ->seconds(false)
                            ->after('available_from'),
                        TextInput::make('sort_order')->label('Orden')->numeric()->default(0)->helperText('Menor primero.'),
                    ]),

                Section::make('Stripe')
                    ->description('Se rellena al sincronizar con Stripe (Fase 4).')
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        TextInput::make('stripe_product_id')->label('Product ID')->disabled()->dehydrated(false),
                        TextInput::make('stripe_price_id')->label('Price ID')->disabled()->dehydrated(false),
                    ]),
            ]);
    }
}
