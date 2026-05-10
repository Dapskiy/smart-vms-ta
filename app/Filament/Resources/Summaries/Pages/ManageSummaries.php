<?php

namespace App\Filament\Resources\Summaries\Pages;

use App\Filament\Resources\Summaries\SummaryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageSummaries extends ManageRecords
{
    protected static string $resource = SummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('Data-Visitor-' . date('Y-m-d')),
                ])
                ->label('Export Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down'),
            CreateAction::make()
                ->label('New Visitor'),
        ];
    }
}
