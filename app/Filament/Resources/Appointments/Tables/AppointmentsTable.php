<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor.name')
                    ->label('Nama Tamu Utama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    // PERBAIKAN 1: Tambahkan ? sebelum string agar kebal dari data kosong
                    ->color(fn(?string $state): string => match ($state) {
                        'appointment' => 'success',
                        'walkin', 'walk_in', 'walk-in' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('visit_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('visit_time')
                    ->label('Jam')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('pax')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('vehicle_number')
                    ->searchable(),
                TextColumn::make('token')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // 1. Tombol Check-In
                Action::make('check_in')
                    ->label('Check In')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('success')
                    // PERBAIKAN 2: Tambahkan ? pada Appointment dan ?-> pada pemanggilan status
                    ->visible(fn(?Appointment $record) => $record?->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Check-in')
                    ->modalDescription('Apakah tamu ini sudah tiba di lokasi dan ingin di-check-in?')
                    ->action(function (Appointment $record) {
                        $record->update(['status' => 'active']);

                        Notification::make()
                            ->title('Berhasil Check-in')
                            ->body("Tamu {$record->visitor->name} telah memasuki area.")
                            ->success()
                            ->send();
                    }),

                // 2. Tombol Check-Out
                Action::make('check_out')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->color('danger')
                    // PERBAIKAN 3: Sama seperti di atas, gunakan parameter nullable
                    ->visible(fn(?Appointment $record) => $record?->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Check-out')
                    ->modalDescription('Apakah kunjungan sudah selesai? Data tamu ini akan dipindahkan ke halaman Summary.')
                    ->action(function (Appointment $record) {
                        $record->update(['status' => 'completed']);

                        Notification::make()
                            ->title('Berhasil Check-out')
                            ->body("Kunjungan {$record->visitor->name} telah selesai.")
                            ->success()
                            ->send();
                    }),

                // 3. Tombol Copy Link
                Action::make('copy_link')
                    ->label('')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->tooltip('Copy Invitation Link')
                    ->action(function (Appointment $record, \Livewire\Component $livewire) {
                        $url = route('guest.invitation', ['token' => $record->token]);
                        $livewire->dispatch('copy-to-clipboard', text: $url);

                        Notification::make()
                            ->success()
                            ->title('Link Berhasil Disalin')
                            ->send();
                    }),

                // 4. Tombol View Detail
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

                // 5. Tombol Edit
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit'),

                // 6. Tombol Delete
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
}
