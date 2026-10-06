<?php

namespace App\Filament\Resources\ReservedNames\Pages;

use App\Filament\Resources\ReservedNames\ReservedNameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReservedNames extends ListRecords
{
    protected static string $resource = ReservedNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
