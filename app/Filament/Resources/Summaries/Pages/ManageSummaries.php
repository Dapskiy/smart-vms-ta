<?php

namespace App\Filament\Resources\Summaries\Pages;

use App\Filament\Resources\Summaries\SummaryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSummaries extends ManageRecords
{
    protected static string $resource = SummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
