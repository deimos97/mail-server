<?php

namespace App\Filament\Resources\Experiments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class ExperimentForm
{
    public static function configure(Schema $schema): Schema
    {
        // Textos de la landing que una variante puede cambiar (config/landing.php)
        $texts = collect(Arr::dot(config('landing')))->filter(fn ($v) => is_string($v))
            ->keys()->reject(fn ($k) => str_starts_with($k, 'legal.'))->values();

        return $schema
            ->columns(2)
            ->components([
                Section::make('Prueba')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(80),
                        TextInput::make('key')
                            ->label('Clave')
                            ->helperText('Va a PostHog como $feature/clave. No cambiarla con la prueba en marcha.')
                            ->required()->alphaDash()->maxLength(40)->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->label('Hipótesis y qué se mide')
                            ->placeholder('Ej.: un título más directo sube el paso name_chosen. Embudo: $pageview → name_checked → name_chosen → signup_completed.')
                            ->rows(2)->columnSpanFull(),
                        Toggle::make('is_active')->label('En marcha')->helperText('Solo cuenta si hay al menos dos variantes.'),
                        DateTimePicker::make('starts_at')->label('Empieza')->seconds(false),
                        DateTimePicker::make('ends_at')->label('Termina')->seconds(false),
                    ]),

                Section::make('Variantes')
                    ->description('A cada visitante le toca una según el peso (por ejemplo 50 / 50). La primera suele ser "control" (la web tal cual).')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('variants')
                            ->hiddenLabel()
                            ->default([['key' => 'control', 'weight' => 50, 'overrides' => []], ['key' => 'b', 'weight' => 50, 'overrides' => []]])
                            ->minItems(2)
                            ->columns(2)
                            ->itemLabel(fn (array $state) => ($state['key'] ?? '?').' · '.($state['weight'] ?? 0))
                            ->schema([
                                TextInput::make('key')->label('Variante')->required()->alphaDash()->maxLength(20),
                                TextInput::make('weight')->label('Peso')->numeric()->minValue(0)->maxValue(100)->required()->default(50),
                                KeyValue::make('overrides')
                                    ->label('Textos que cambia')
                                    ->keyLabel('Texto')
                                    ->valueLabel('Nuevo texto')
                                    ->helperText('Disponibles: '.$texts->implode(', ').'. Vacío = como en la web.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
