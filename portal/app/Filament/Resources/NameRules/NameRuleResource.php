<?php

namespace App\Filament\Resources\NameRules;

use App\Filament\Resources\NameRules\Pages\EditNameRule;
use App\Filament\Resources\NameRules\Pages\ListNameRules;
use App\Filament\Resources\NameRules\Schemas\NameRuleForm;
use App\Filament\Resources\NameRules\Tables\NameRulesTable;
use App\Models\NameRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class NameRuleResource extends Resource
{
    protected static ?string $model = NameRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Nombres';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Reglas de nombre';

    protected static ?string $modelLabel = 'regla de nombre';

    protected static ?string $pluralModelLabel = 'reglas de nombre';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return NameRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NameRulesTable::configure($table);
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
            'index' => ListNameRules::route('/'),
            'edit' => EditNameRule::route('/{record}/edit'),
        ];
    }
}
