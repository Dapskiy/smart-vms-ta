<?php

namespace App\Filament\Resources\Summaries; // Sesuaikan namespace dengan lokasi file Anda

use App\Filament\Resources\Summaries\Pages; // Sesuaikan juga jika foldernya Summaries
use App\Models\Appointment;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;    // <--- Tambahkan import ini
use BackedEnum;  // <--- Tambahkan import ini
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class SummaryResource extends Resource
{
    protected static ?string $model = Appointment::class;

    // Perbaikan: Tipe data disesuaikan 100% dengan aturan Filament & PHP 8
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Appointments';
    protected static ?string $navigationLabel = 'Summary';
    protected static ?string $pluralModelLabel = 'Summary Kunjungan';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'completed');
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table)
            ->actions([
                // View Detail
                Action::make('view_detail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Lihat Detail')
                    ->color('info')
                    ->modalHeading(fn(Appointment $record) => 'Detail Visitor (' . ($record->visit_id ?? $record->token) . ')')
                    ->modalContent(fn(Appointment $record) => view('filament.appointments.detail-modal', ['record' => $record]))
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
