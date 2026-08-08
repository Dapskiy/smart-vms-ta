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
                ->visible(fn () => auth()->user()->can('export_summary') || auth()->user()->can('ViewAny:Visitor'))
                ->exports([
                    \App\Filament\Exports\SummaryExcelExport::make()
                        ->withFilename(fn () => (function () {
                            $referer = request()->header('referer');
                            parse_str(parse_url($referer, PHP_URL_QUERY) ?? '', $urlParams);
                            $type = $urlParams['type'] ?? 'range';
                            $fileName = 'Summary Visitor Keseluruhan';

                            if ($type === 'month' && !empty($urlParams['month']) && !empty($urlParams['year'])) {
                                $dateObj = \Carbon\Carbon::createFromDate($urlParams['year'], $urlParams['month'], 1);
                                $fileName = "Summary Visitor " . $dateObj->translatedFormat('F Y');
                            } elseif ($type === 'year' && !empty($urlParams['year'])) {
                                $fileName = "Summary Visitor Tahun " . $urlParams['year'];
                            } elseif ($type === 'range' || !empty($urlParams['start_date']) || !empty($urlParams['end_date'])) {
                                $startDate = $urlParams['start_date'] ?? null;
                                $endDate = $urlParams['end_date'] ?? null;

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
                            return $fileName;
                        })())
                        ->modifyQueryUsing(function (Builder $query) {
                            $referer = request()->header('referer');
                            parse_str(parse_url($referer, PHP_URL_QUERY) ?? '', $urlParams);
                            $type = $urlParams['type'] ?? 'range';

                            $currentUser = auth()->user();
                            if ($currentUser && !$currentUser->hasRole('super_admin') && $currentUser->pic) {
                                $query->where('pic_id', $currentUser->pic->id);
                            }

                            if ($type === 'range') {
                                if (!empty($urlParams['start_date']) && !empty($urlParams['end_date'])) {
                                    $from = \Carbon\Carbon::parse($urlParams['start_date'])->startOfDay();
                                    $to = \Carbon\Carbon::parse($urlParams['end_date'])->endOfDay();
                                    $query->whereBetween('updated_at', [$from, $to]);
                                } elseif (!empty($urlParams['start_date'])) {
                                    $from = \Carbon\Carbon::parse($urlParams['start_date'])->startOfDay();
                                    $query->where('updated_at', '>=', $from);
                                } elseif (!empty($urlParams['end_date'])) {
                                    $to = \Carbon\Carbon::parse($urlParams['end_date'])->endOfDay();
                                    $query->where('updated_at', '<=', $to);
                                }
                            } elseif ($type === 'month' && !empty($urlParams['month']) && !empty($urlParams['year'])) {
                                $query->whereMonth('updated_at', $urlParams['month'])
                                      ->whereYear('updated_at', $urlParams['year']);
                            } elseif ($type === 'year' && !empty($urlParams['year'])) {
                                $query->whereYear('updated_at', $urlParams['year']);
                            }

                            return $query;
                        })
                        ->withColumns([
                            \pxlrbt\FilamentExcel\Columns\Column::make('no')->heading('No')->getStateUsing(static function () { static $row = 0; return ++$row; }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visitor.name')->heading('Nama Visitor'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visitor.company')->heading('Instansi'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visitor.phone')
                                ->heading('No. Telepon')
                                ->getStateUsing(function ($record) {
                                    $phone = $record->visitor?->phone;
                                    if (empty($phone)) return '-';
                                    if (\App\Helpers\PhoneMaskHelper::canReveal()) return $phone;
                                    return \App\Helpers\PhoneMaskHelper::mask($phone);
                                }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visit_date')->heading('Tanggal Berkunjung')->getStateUsing(function ($record) {
                                return $record->visit_date ? \Carbon\Carbon::parse($record->visit_date)->format('d M Y') : '-';
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('visit_time')->heading('Checkin')->getStateUsing(function ($record) {
                                return $record->visit_time ? \Carbon\Carbon::parse($record->visit_time)->format('H:i') : '-';
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('checkout_time')->heading('Checkout')->getStateUsing(function ($record) {
                                if ($record->checkout_time) {
                                    return \Carbon\Carbon::parse($record->checkout_time)->format('H:i');
                                }
                                return \Carbon\Carbon::parse($record->updated_at)->format('H:i');
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('pic.name')->heading('PIC')->getStateUsing(function ($record) {
                                return $record->pic?->name ?? '-';
                            }),
                            \pxlrbt\FilamentExcel\Columns\Column::make('purpose')->heading('Keperluan')->getStateUsing(function ($record) {
                                return $record->purpose ?? '-';
                            }),
                        ]),
                ])
                ->label('Export Data')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down'),
        ];
    }
}
