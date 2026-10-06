<?php

namespace App\Filament\Resources\NamePriceTiers\Pages;

use App\Filament\Resources\NamePriceTiers\NamePriceTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNamePriceTiers extends ListRecords
{
    protected static string $resource = NamePriceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
