<?php

namespace App\Filament\Resources\Pics\Pages;

use App\Filament\Resources\Pics\PicResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePic extends CreateRecord
{
    protected static string $resource = PicResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
