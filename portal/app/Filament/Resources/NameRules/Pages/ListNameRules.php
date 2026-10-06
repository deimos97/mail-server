<?php

namespace App\Filament\Resources\NameRules\Pages;

use App\Filament\Resources\NameRules\NameRuleResource;
use Filament\Resources\Pages\ListRecords;

class ListNameRules extends ListRecords
{
    protected static string $resource = NameRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
