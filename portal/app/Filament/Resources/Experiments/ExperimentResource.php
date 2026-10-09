<?php

namespace App\Filament\Resources\Experiments;

use App\Filament\Resources\Experiments\Pages\CreateExperiment;
use App\Filament\Resources\Experiments\Pages\EditExperiment;
use App\Filament\Resources\Experiments\Pages\ListExperiments;
use App\Filament\Resources\Experiments\Schemas\ExperimentForm;
use App\Filament\Resources\Experiments\Tables\ExperimentsTable;
use App\Models\Experiment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExperimentResource extends Resource
{
    protected static ?string $model = Experiment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Web';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pruebas A/B';

    protected static ?string $modelLabel = 'prueba A/B';

    protected static ?string $pluralModelLabel = 'pruebas A/B';

    public static function form(Schema $schema): Schema
    {
        return ExperimentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExperimentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExperiments::route('/'),
            'create' => CreateExperiment::route('/create'),
            'edit' => EditExperiment::route('/{record}/edit'),
        ];
    }
}
