<?php

namespace App\Filament\Resources\KioskLocations\Pages;

use App\Filament\Resources\KioskLocations\KioskLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKioskLocations extends ListRecords
{
    protected static string $resource = KioskLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
