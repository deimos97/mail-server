<?php

namespace App\Filament\Resources\ReservedNames;

use App\Filament\Resources\ReservedNames\Pages\CreateReservedName;
use App\Filament\Resources\ReservedNames\Pages\EditReservedName;
use App\Filament\Resources\ReservedNames\Pages\ListReservedNames;
use App\Filament\Resources\ReservedNames\Schemas\ReservedNameForm;
use App\Filament\Resources\ReservedNames\Tables\ReservedNamesTable;
use App\Models\ReservedName;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReservedNameResource extends Resource
{
    protected static ?string $model = ReservedName::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = 'Nombres';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Nombres reservados';

    protected static ?string $modelLabel = 'nombre reservado';

    protected static ?string $pluralModelLabel = 'nombres reservados';

    protected static ?string $recordTitleAttribute = 'local_part';

    public static function form(Schema $schema): Schema
    {
        return ReservedNameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservedNamesTable::configure($table);
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
            'index' => ListReservedNames::route('/'),
            'create' => CreateReservedName::route('/create'),
            'edit' => EditReservedName::route('/{record}/edit'),
        ];
    }
}
