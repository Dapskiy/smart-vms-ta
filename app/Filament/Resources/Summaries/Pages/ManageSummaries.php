<?php

namespace App\Filament\Resources\Summaries\Pages;

use App\Filament\Resources\Summaries\SummaryResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ManageSummaries extends ManageRecords
{
    protected static string $resource = SummaryResource::class;

    protected function getHeaderActions(): array
    {
        // Menentukan nama file dinamis berdasarkan parameter URL/filter yang aktif
        $type = request('type', empty(request()->all()) ? 'range' : request('type'));
        $fileName = 'Summary Visitor Keseluruhan';

        if ($type === 'month' && request()->filled('month') && request()->filled('year')) {
            $dateObj = \Carbon\Carbon::createFromDate(request('year'), request('month'), 1);
            $fileName = "Summary Visitor " . $dateObj->translatedFormat('F Y');
        } elseif ($type === 'year' && request()->filled('year')) {
            $fileName = "Summary Visitor Tahun " . request('year');
        } elseif ($type === 'range' || request()->filled('start_date') || request()->filled('end_date')) {
            $startDate = request('start_date');
            $endDate = request('end_date');

            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                $fileName = "Summary Visitor " . $start->translatedFormat('d F Y') . " - " . $end->translatedFormat('d F Y');
            } elseif ($startDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $fileName = "Summary Visitor Sejak " . $start->translatedFormat('d F Y');
            } elseif ($endDate) {
                $end = \Carbon\Carbon::parse($endDate);
                $fileName = "Summary Visitor Sampai " . $end->translatedFormat('d F Y');
            }
        }

        return [
            Action::make('filter_date')
                ->label('Filter Tanggal')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->form([
                    Select::make('filter_type')
                        ->label('Tipe Filter')
                        ->options([
                            'range' => 'Rentang Tanggal',
                            'month' => 'Bulanan',
                            'year' => 'Tahunan',
                        ])
                        ->default(request('type', empty(request()->all()) ? 'range' : request('type')))
                        ->live(),

                    DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->default(request('start_date') ? date_create(request('start_date'))->format('Y-m-d') : null)
                        ->native(false)
                        ->visible(fn ($get) => $get('filter_type') === 'range'),

                    DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->default(request('end_date') ? date_create(request('end_date'))->format('Y-m-d') : null)
                        ->native(false)
                        ->visible(fn ($get) => $get('filter_type') === 'range'),

                    Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
                        ])
                        ->default(request('month') ?? date('n'))
                        ->visible(fn ($get) => $get('filter_type') === 'month'),

                    Select::make('year')
                        ->label('Tahun')
                        ->options(function () {
                            $currentYear = date('Y');
                            $years = [];
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                $years[$y] = $y;
                            }
                            return $years;
                        })
                        ->default(request('year') ?? date('Y'))
                        ->visible(fn ($get) => $get('filter_type') === 'month'),

                    TextInput::make('year_only')
                        ->label('Tahun')
                        ->numeric()
                        ->default(request('year') ?? date('Y'))
                        ->visible(fn ($get) => $get('filter_type') === 'year'),
                ])
                ->action(function (array $data) {
                    $type = $data['filter_type'] ?? 'range';
                    $baseUrl = \App\Filament\Resources\Summaries\SummaryResource::getUrl('index');
                    $params = ['type' => $type];

                    if ($type === 'range') {
                        $startDate = $data['start_date'] ?? null;
                        $endDate = $data['end_date'] ?? null;

                        if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                            Notification::make()
                                ->title('Tanggal akhir tidak boleh lebih awal dari tanggal awal!')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($startDate) $params['start_date'] = $startDate;
                        if ($endDate) $params['end_date'] = $endDate;
                    } elseif ($type === 'month') {
                        if (!empty($data['month'])) $params['month'] = $data['month'];
                        if (!empty($data['year'])) $params['year'] = $data['year'];
                    } elseif ($type === 'year') {
                        if (!empty($data['year_only'])) $params['year'] = $data['year_only'];
                    }

                    $queryString = http_build_query($params);
                    return redirect($baseUrl . ($queryString ? '?' . $queryString : ''));
                }),

            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()
                ->visible(fn () => auth()->user()->can('export_summary'))
                ->exports([
                    \App\Filament\Exports\SummaryExcelExport::make()
                        ->withFilename($fileName)
                        ->modifyQueryUsing(function (Builder $query) {
                            $referer = request()->header('referer');
                            parse_str(parse_url($referer, PHP_URL_QUERY) ?? '', $urlParams);
                            $type = $urlParams['type'] ?? 'range';

                            $query->whereHas('appointments', function (Builder $q) {
                                $q->whereIn('status', ['completed', 'checkout', 'inactive']);
                            });

                            if ($type === 'range') {
                                if (!empty($urlParams['start_date']) && !empty($urlParams['end_date'])) {
                                    $from = \Carbon\Carbon::parse($urlParams['start_date'])->startOfDay();
                                    $to = \Carbon\Carbon::parse($urlParams['end_date'])->endOfDay();
                                    
                                    $query->whereHas('appointments', function ($q) use ($from, $to) {
                                        $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                                          ->whereBetween('updated_at', [$from, $to]);
                                    });
                                } elseif (!empty($urlParams['start_date'])) {
                                    $from = \Carbon\Carbon::parse($urlParams['start_date'])->startOfDay();
                                    $query->whereHas('appointments', function ($q) use ($from) {
                                        $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                                          ->where('updated_at', '>=', $from);
                                    });
                                } elseif (!empty($urlParams['end_date'])) {
                                    $to = \Carbon\Carbon::parse($urlParams['end_date'])->endOfDay();
                                    $query->whereHas('appointments', function ($q) use ($to) {
                                        $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                                          ->where('updated_at', '<=', $to);
                                    });
                                }
                            } elseif ($type === 'month' && !empty($urlParams['month']) && !empty($urlParams['year'])) {
                                $month = $urlParams['month'];
                                $year = $urlParams['year'];
                                
                                $query->whereHas('appointments', function ($q) use ($month, $year) {
                                    $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                                      ->whereMonth('updated_at', $month)
                                      ->whereYear('updated_at', $year);
                                });
                            } elseif ($type === 'year' && !empty($urlParams['year'])) {
                                $year = $urlParams['year'];
                                
                                $query->whereHas('appointments', function ($q) use ($year) {
                                    $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                                      ->whereYear('updated_at', $year);
                                });
                            }

                            return $query;
                        })
                        ->withColumns([
                            \pxlrbt\FilamentExcel\Columns\Column::make('no')->heading('No')->getStateUsing(static function () { static $row = 0; return ++$row; }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('name')->heading('Nama Visitor'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('company')->heading('Instansi'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('phone')
                                ->heading('No. Telepon')
                                ->getStateUsing(function ($record) {
                                    if (empty($record->phone)) return '-';
                                    if (auth()->user()->hasRole('super_admin')) return $record->phone;
                                    return \Illuminate\Support\Str::mask($record->phone, '*', 4, -4);
                                }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visit_date')->heading('Tanggal Berkunjung')->getStateUsing(function ($record) {
                                $lastAppointment = $record->appointments()->whereIn('status', ['completed', 'checkout', 'inactive'])->latest('visit_date')->first();
                                return $lastAppointment && $lastAppointment->visit_date ? \Carbon\Carbon::parse($lastAppointment->visit_date)->format('d M Y') : '-';
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visit_time')->heading('Checkin')->getStateUsing(function ($record) {
                                $lastAppointment = $record->appointments()->whereIn('status', ['completed', 'checkout', 'inactive'])->latest('visit_date')->first();
                                return $lastAppointment && $lastAppointment->visit_time ? \Carbon\Carbon::parse($lastAppointment->visit_time)->format('H:i') : '-';
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('checkout_time')->heading('Checkout')->getStateUsing(function ($record) {
                                $lastAppointment = $record->appointments()->whereIn('status', ['completed', 'checkout', 'inactive'])->latest('visit_date')->first();
                                if (!$lastAppointment) return '-';
                                if ($lastAppointment->checkout_time) {
                                    return \Carbon\Carbon::parse($lastAppointment->checkout_time)->format('H:i');
                                }
                                return \Carbon\Carbon::parse($lastAppointment->updated_at)->format('H:i');
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('pic')->heading('PIC')->getStateUsing(function ($record) {
                                $lastAppointment = $record->appointments()->whereIn('status', ['completed', 'checkout', 'inactive'])->latest('visit_date')->first();
                                return $lastAppointment && $lastAppointment->pic ? $lastAppointment->pic->name : '-';
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('purpose')->heading('Keperluan')->getStateUsing(function ($record) {
                                $lastAppointment = $record->appointments()->whereIn('status', ['completed', 'checkout', 'inactive'])->latest('visit_date')->first();
                                return $lastAppointment ? $lastAppointment->purpose : '-';
                            }),
                        ]),
                ])
                ->label('Export Data')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down'),
        ];
    }
}
