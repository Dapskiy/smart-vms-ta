<?php

namespace App\Filament\Resources\Pics\Pages;

use App\Filament\Resources\Pics\PicResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPic extends EditRecord
{
    protected static string $resource = PicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
