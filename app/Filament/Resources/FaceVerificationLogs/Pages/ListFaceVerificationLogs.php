<?php

namespace App\Filament\Resources\FaceVerificationLogs\Pages;

use App\Filament\Resources\FaceVerificationLogs\FaceVerificationLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFaceVerificationLogs extends ListRecords
{
    protected static string $resource = FaceVerificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
