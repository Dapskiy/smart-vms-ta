<?php

namespace App\Filament\Resources\Summaries; // Sesuaikan namespace dengan lokasi file Anda

use App\Filament\Resources\Summaries\Pages; // Sesuaikan juga jika foldernya Summaries
use App\Models\Appointment;
use App\Models\Visitor;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class SummaryResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Janji Temu dan PIC';
    protected static ?string $navigationLabel = 'Ringkasan';
    protected static ?string $pluralModelLabel = 'Ringkasan Kunjungan';
    protected static ?int $navigationSort = 4; // Positioning di bawah Room Master

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),

                // Semua kolom di bawah membaca dari relasi yang sudah di-eager load
                // di getEloquentQuery(), sehingga TIDAK ada query tambahan per baris.
                TextColumn::make('appointments.visit_date')
                    ->label('Tanggal Berkunjung')
                    ->getStateUsing(function (Visitor $record) {
                        $apt = $record->appointments->first();
                        if (!$apt?->visit_date) return '-';
                        return \Carbon\Carbon::parse($apt->visit_date)->format('d M Y');
                    })
                    ->sortable(),

                TextColumn::make('appointments.visit_time')
                    ->label('Checkin')
                    ->getStateUsing(function (Visitor $record) {
                        $apt = $record->appointments->first();
                        if (!$apt?->visit_time) return '-';
                        $time = $apt->visit_time;
                        return is_string($time)
                            ? \Carbon\Carbon::parse($time)->format('H:i')
                            : $time->format('H:i');
                    }),

                TextColumn::make('appointments.checkout_time')
                    ->label('Checkout')
                    ->badge()
                    ->color(function (Visitor $record) {
                        $apt = $record->appointments->first();
                        if ($apt?->checkout_method === 'system') {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->getStateUsing(function (Visitor $record) {
                        $apt = $record->appointments->first();
                        if (!$apt) return '-';
                        
                        $timeStr = '-';
                        if ($apt->checkout_time) {
                            $time = $apt->checkout_time;
                            $timeStr = is_string($time)
                                ? \Carbon\Carbon::parse($time)->format('H:i')
                                : $time->format('H:i');
                        } else {
                            $timeStr = \Carbon\Carbon::parse($apt->updated_at)->format('H:i');
                        }

                        if ($apt->checkout_method === 'system') {
                            return $timeStr . ' (Auto)';
                        }
                        return $timeStr;
                    }),

                TextColumn::make('name')
                    ->label('Nama Visitor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company')
                    ->label('Instansi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('appointments.pic')
                    ->label('PIC')
                    ->getStateUsing(function (Visitor $record) {
                        $apt = $record->appointments->first();
                        return $apt?->pic?->name ?? '-';
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('appointments.purpose')
                    ->label('Keperluan')
                    ->getStateUsing(function (Visitor $record) {
                        $apt = $record->appointments->first();
                        if (!$apt?->purpose) return '-';
                        return \Illuminate\Support\Str::limit($apt->purpose, 50, '...');
                    })
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // View Detail
                Action::make('view_detail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Lihat Detail')
                    ->color('info')
                    ->modalHeading(fn(Visitor $record) => 'Detail Pengunjung: ' . $record->name)
                    ->modalContent(function (Visitor $record) {
                        // Tampilkan appointment terbaru yang completed / checkout
                        $appointment = $record->appointments()
                            ->whereIn('status', ['completed', 'checkout', 'inactive'])
                            ->latest('visit_date')
                            ->first();
                        return view('filament.appointments.detail-modal', ['record' => $appointment ?? $record->appointments()->first()]);
                    })
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // Edit
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit'),

                // Delete
                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSummaries::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // Override izin melihat menu di sidebar dan daftar tabel
    public static function canViewAny(): bool
    {
        return auth()->user()->can('ViewAny:Visitor');
    }

    // (Opsional) Mencegah user mengakses data detail jika diakali lewat URL
    public static function canView($record): bool
    {
        return auth()->user()->can('View:Visitor');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $currentUser = auth()->user();
        
        if ($currentUser && !$currentUser->hasRole('super_admin') && $currentUser->pic) {
            $picId = $currentUser->pic->id;
            
            $query->with(['appointments' => function ($q) use ($picId) {
                $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                  ->where('pic_id', $picId)
                  ->with('pic')
                  ->latest('visit_date')
                  ->limit(1);
            }])
            ->whereHas('appointments', function (Builder $q) use ($picId) {
                $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                  ->where('pic_id', $picId);
            });
        } else {
            // Eager load: appointment terbaru (completed/checkout/inactive) beserta relasi PIC
            // Ini mencegah N+1 query yang sebelumnya terjadi 6x per baris di tabel
            $query->with(['appointments' => function ($q) {
                $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                  ->with('pic')
                  ->latest('visit_date')
                  ->limit(1);
            }])
            ->whereHas('appointments', function (Builder $q) {
                $q->whereIn('status', ['completed', 'checkout', 'inactive']);
            });
        }

        $type = request('type', empty(request()->all()) ? 'range' : request('type'));

        if ($type === 'range') {
            if (request()->filled('start_date') && request()->filled('end_date')) {
                $from = \Carbon\Carbon::parse(request('start_date'))->startOfDay();
                $to = \Carbon\Carbon::parse(request('end_date'))->endOfDay();
                
                $query->whereHas('appointments', function ($q) use ($from, $to) {
                    $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                      ->whereBetween('updated_at', [$from, $to]);
                });
            } elseif (request()->filled('start_date')) {
                $from = \Carbon\Carbon::parse(request('start_date'))->startOfDay();
                $query->whereHas('appointments', function ($q) use ($from) {
                    $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                      ->where('updated_at', '>=', $from);
                });
            } elseif (request()->filled('end_date')) {
                $to = \Carbon\Carbon::parse(request('end_date'))->endOfDay();
                $query->whereHas('appointments', function ($q) use ($to) {
                    $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                      ->where('updated_at', '<=', $to);
                });
            }
        } elseif ($type === 'month' && request()->filled('month') && request()->filled('year')) {
            $month = request('month');
            $year = request('year');
            
            $query->whereHas('appointments', function ($q) use ($month, $year) {
                $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                  ->whereMonth('updated_at', $month)
                  ->whereYear('updated_at', $year);
            });
        } elseif ($type === 'year' && request()->filled('year')) {
            $year = request('year');
            
            $query->whereHas('appointments', function ($q) use ($year) {
                $q->whereIn('status', ['completed', 'checkout', 'inactive'])
                  ->whereYear('updated_at', $year);
            });
        }

        return $query;
    }
}
