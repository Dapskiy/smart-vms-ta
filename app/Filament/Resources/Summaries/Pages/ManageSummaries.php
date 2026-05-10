<?php

namespace App\Filament\Resources\Summaries\Pages;

use App\Filament\Resources\Summaries\SummaryResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Filament\Resources\Pages\ManageRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageSummaries extends ManageRecords
{
    protected static string $resource = SummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter_date')
                ->label('Filter Tanggal')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->form([
                    DateRangePicker::make('date_range')
                        ->label('Pilih Rentang Tanggal')
                        ->default(request('date_range'))
                        ->autofocus(),
                ])
                ->action(function (array $data) {
                    return redirect(request()->url() . '?date_range=' . urlencode($data['date_range'] ?? ''));
                }),
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
