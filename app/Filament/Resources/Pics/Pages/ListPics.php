<?php

namespace App\Filament\Resources\Pics\Pages;

use App\Filament\Resources\Pics\PicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPics extends ListRecords
{
    protected static string $resource = PicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
