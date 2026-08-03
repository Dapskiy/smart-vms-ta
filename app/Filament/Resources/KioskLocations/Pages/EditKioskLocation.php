<?php

namespace App\Filament\Resources\KioskLocations\Pages;

use App\Filament\Resources\KioskLocations\KioskLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKioskLocation extends EditRecord
{
    protected static string $resource = KioskLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
