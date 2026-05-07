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
use UnitEnum;    // <--- Tambahkan import ini
use BackedEnum;  // <--- Tambahkan import ini

class SummaryResource extends Resource
{
    protected static ?string $model = Visitor::class;

    // Perbaikan: Tipe data disesuaikan 100% dengan aturan Filament & PHP 8
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Appointments';
    protected static ?string $navigationLabel = 'Summary';
    protected static ?string $pluralModelLabel = 'Summary Kunjungan';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('appointments.visit_date')
                    ->label('Tanggal Berkunjung')
                    ->getStateUsing(function (Visitor $record) {
                        $lastAppointment = $record->appointments()
                            ->where('status', 'completed')
                            ->latest('visit_date')
                            ->first();

                        if (!$lastAppointment || !$lastAppointment->visit_date) {
                            return '-';
                        }

                        return \Carbon\Carbon::parse($lastAppointment->visit_date)->format('d M Y');
                    })
                    ->sortable(),

                TextColumn::make('appointments.visit_time')
                    ->label('Checkin')
                    ->getStateUsing(function (Visitor $record) {
                        $lastAppointment = $record->appointments()
                            ->where('status', 'completed')
                            ->latest('visit_date')
                            ->first();

                        if (!$lastAppointment || !$lastAppointment->visit_time) {
                            return '-';
                        }

                        $time = $lastAppointment->visit_time;
                        return is_string($time)
                            ? \Carbon\Carbon::parse($time)->format('H:i')
                            : $time->format('H:i');
                    }),

                TextColumn::make('appointments.checkout_time')
                    ->label('Checkout')
                    ->getStateUsing(function (Visitor $record) {
                        $lastAppointment = $record->appointments()
                            ->where('status', 'completed')
                            ->latest('visit_date')
                            ->first();

                        if (!$lastAppointment) {
                            return '-';
                        }

                        if ($lastAppointment->checkout_time) {
                            $time = $lastAppointment->checkout_time;
                            return is_string($time)
                                ? \Carbon\Carbon::parse($time)->format('H:i')
                                : $time->format('H:i');
                        }

                        // Fallback ke updated_at
                        return \Carbon\Carbon::parse($lastAppointment->updated_at)->format('H:i');
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
                        $lastAppointment = $record->appointments()
                            ->where('status', 'completed')
                            ->latest('visit_date')
                            ->first();

                        if (!$lastAppointment || !$lastAppointment->pic) {
                            return '-';
                        }

                        return $lastAppointment->pic->name ?? '-';
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('appointments.purpose')
                    ->label('Keperluan')
                    ->getStateUsing(function (Visitor $record) {
                        $lastAppointment = $record->appointments()
                            ->where('status', 'completed')
                            ->latest('visit_date')
                            ->first();

                        if (!$lastAppointment || !$lastAppointment->purpose) {
                            return '-';
                        }

                        return \Illuminate\Support\Str::limit($lastAppointment->purpose, 50, '...');
                    })
                    ->wrap(),
            ])
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
                        // Tampilkan appointment terbaru yang completed
                        $appointment = $record->appointments()
                            ->where('status', 'completed')
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
        // Sesuaikan nama string ini dengan Permission Name yang Anda buat di Langkah 1
        return auth()->user()->can('viewany summary');
    }

    // (Opsional) Mencegah user mengakses data detail jika diakali lewat URL
    public static function canView($record): bool
    {
        return auth()->user()->can('viewany summary');
    }
}
