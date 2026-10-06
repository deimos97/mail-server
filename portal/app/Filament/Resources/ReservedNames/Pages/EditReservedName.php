<?php

namespace App\Filament\Resources\ReservedNames\Pages;

use App\Filament\Resources\ReservedNames\ReservedNameResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReservedName extends EditRecord
{
    protected static string $resource = ReservedNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
