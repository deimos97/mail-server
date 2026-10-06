<?php

namespace App\Filament\Resources\NamePriceTiers;

use App\Filament\Resources\NamePriceTiers\Pages\CreateNamePriceTier;
use App\Filament\Resources\NamePriceTiers\Pages\EditNamePriceTier;
use App\Filament\Resources\NamePriceTiers\Pages\ListNamePriceTiers;
use App\Filament\Resources\NamePriceTiers\Schemas\NamePriceTierForm;
use App\Filament\Resources\NamePriceTiers\Tables\NamePriceTiersTable;
use App\Models\NamePriceTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NamePriceTierResource extends Resource
{
    protected static ?string $model = NamePriceTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static string|UnitEnum|null $navigationGroup = 'Nombres';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Tramos de precio';

    protected static ?string $modelLabel = 'tramo de precio';

    protected static ?string $pluralModelLabel = 'tramos de precio';

    public static function form(Schema $schema): Schema
    {
        return NamePriceTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NamePriceTiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNamePriceTiers::route('/'),
            'create' => CreateNamePriceTier::route('/create'),
            'edit' => EditNamePriceTier::route('/{record}/edit'),
        ];
    }
}
