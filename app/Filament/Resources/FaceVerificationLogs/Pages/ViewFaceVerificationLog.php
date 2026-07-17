<?php

namespace App\Filament\Resources\FaceVerificationLogs\Pages;

use App\Filament\Resources\FaceVerificationLogs\FaceVerificationLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFaceVerificationLog extends ViewRecord
{
    protected static string $resource = FaceVerificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
