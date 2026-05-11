<?php

namespace App\Filament\Resources\Summaries\Pages;

use App\Filament\Resources\Summaries\SummaryResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;

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
                    DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->default(request('start_date') ? date_create(request('start_date'))->format('Y-m-d') : null)
                        ->native(false)
                        ->autofocus(),
                    DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->default(request('end_date') ? date_create(request('end_date'))->format('Y-m-d') : null)
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;

                    // Validasi: end_date tidak boleh lebih awal dari start_date
                    if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                        Notification::make()
                            ->title('Tanggal akhir tidak boleh lebih awal dari tanggal awal!')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Buat URL dengan parameter start_date dan end_date
                    $baseUrl = request()->url();
                    $params = [];

                    if ($startDate) {
                        $params['start_date'] = $startDate;
                    }
                    if ($endDate) {
                        $params['end_date'] = $endDate;
                    }

                    $queryString = http_build_query($params);
                    $redirectUrl = $baseUrl . ($queryString ? '?' . $queryString : '');

                    return redirect($redirectUrl);
                }),

            Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    Notification::make()
                        ->title('Fitur Export sedang dalam perbaikan')
                        ->warning()
                        ->send();
                }),
        ];
    }
}

