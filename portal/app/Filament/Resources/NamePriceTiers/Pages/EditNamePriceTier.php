<?php

namespace App\Filament\Resources\NamePriceTiers\Pages;

use App\Filament\Resources\NamePriceTiers\NamePriceTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNamePriceTier extends EditRecord
{
    protected static string $resource = NamePriceTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
