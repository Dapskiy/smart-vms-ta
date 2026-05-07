<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Carbon\Carbon;

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
                ViewColumn::make('visitor_display')
                    ->label('Nama Pengunjung')
                    ->view('components.visitor-list-column')
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('visitor_id', 'like', "%{$search}%")
                            ->orWhereJsonContains('companions', [['name' => $search]]);
                    }),
                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
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
                TextColumn::make('checkin_time')
                    ->label('Checkin')
                    ->time('H:i')
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
                    ->visible(fn(?Appointment $record) => $record?->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Check-in')
                    ->modalDescription('Apakah tamu ini sudah tiba di lokasi dan ingin di-check-in?')
                    ->action(function (Appointment $record) {
                        // Hanya validasi untuk tipe 'appointment' (bukan walk-in)
                        if ($record->type === 'appointment') {
                            $now = Carbon::now();

                            // Ambil jadwal terjadwal: visit_date + visit_time (atau jam mulai ruangan jika booking)
                            $scheduledDate = Carbon::parse($record->visit_date);
                            $scheduledTime = $record->should_book_room
                                ? $record->room_start_time
                                : $record->visit_time;

                            // Gabungkan tanggal dan jam menjadi satu DateTime
                            $scheduledDateTime = $scheduledDate->setTimeFromTimeString($scheduledTime);

                            // Hitung window check-in: maksimal 1 jam sebelum jadwal
                            $checkInStart = $scheduledDateTime->copy()->subHour();

                            // Validasi 1: Harus pada tanggal yang sama (visit_date)
                            if ($now->toDateString() !== $scheduledDate->toDateString()) {
                                Notification::make()
                                    ->title('Belum Waktunya Check-in!')
                                    ->body("Check-in hanya bisa dilakukan pada tanggal kunjungan ({$scheduledDate->format('d/m/Y')}).")
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Validasi 2: Harus dalam jendela H-1 jam
                            if ($now->isBefore($checkInStart)) {
                                $remainingTime = $now->diffInMinutes($checkInStart);
                                Notification::make()
                                    ->title('Belum Waktunya Check-in!')
                                    ->body("Check-in dapat dilakukan maksimal 1 jam sebelum jadwal. Waktu check-in dimulai pukul {$checkInStart->format('H:i')} ({$remainingTime} menit lagi).")
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        // Jika semua validasi lolos, proses check-in
                        $record->update([
                            'status' => 'active',
                            'checkin_time' => now()->format('H:i'),
                        ]);

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
                    ->visible(fn(?Appointment $record) => $record?->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Check-out')
                    ->modalDescription('Apakah kunjungan sudah selesai? Data tamu ini akan dipindahkan ke halaman Summary.')
                    ->action(function (Appointment $record) {
                        $record->update([
                            'status' => 'completed',
                            'checkout_time' => now()->format('H:i'),
                        ]);

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
