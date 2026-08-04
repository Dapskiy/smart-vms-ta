<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

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
                TextColumn::make('token')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'approved'  => 'info',
                        'active'    => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        'rejected'  => 'danger',
                        default     => 'primary',
                    })
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // ─────────────────────────────────────────────────────────────
                // 1a. Tombol "Daftarkan Wajah"
                //     Muncul HANYA jika visitor belum punya face_features.
                //     Membuka modal face-scan untuk merekam wajah, LALU check-in.
                // ─────────────────────────────────────────────────────────────
                Action::make('register_face')
                    ->label('Daftarkan Wajah')
                    ->icon('heroicon-o-camera')
                    ->color('warning')
                    ->visible(fn(?Appointment $record): bool =>
                        in_array($record?->status, ['pending', 'approved']) &&
                        empty($record?->visitor?->face_features)
                    )
                    ->modalHeading('Daftarkan Wajah & Check-in')
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn(Appointment $record) =>
                        view('filament.appointments.face-scan', ['record' => $record])
                    )
                    ->action(function (Appointment $record, array $arguments) {
                        // Validasi waktu untuk tipe appointment
                        if ($record->type === 'appointment') {
                            $now = Carbon::now();
                            $scheduledDate = Carbon::parse($record->visit_date);
                            $scheduledTime = $record->should_book_room
                                ? $record->room_start_time
                                : $record->visit_time;
                            $scheduledDateTime = $scheduledDate->setTimeFromTimeString($scheduledTime);
                            $checkInStart = $scheduledDateTime->copy()->subHour();

                            if ($now->toDateString() !== $scheduledDate->toDateString()) {
                                Notification::make()
                                    ->title('Belum Waktunya Check-in!')
                                    ->body("Check-in hanya bisa dilakukan pada tanggal kunjungan ({$scheduledDate->format('d/m/Y')}).")
                                    ->danger()->send();
                                return;
                            }

                            if ($now->isBefore($checkInStart)) {
                                $remaining = $now->diffInMinutes($checkInStart);
                                Notification::make()
                                    ->title('Belum Waktunya Check-in!')
                                    ->body("Check-in dimulai pukul {$checkInStart->format('H:i')} ({$remaining} menit lagi).")
                                    ->danger()->send();
                                return;
                            }
                        }

                        // Simpan face features + foto terenkripsi ke visitor (maks 10)
                        $visitor = $record->visitor;
                        if ($visitor) {
                            if (!empty($arguments['face_features'])) {
                                // Jika dikirim sebagai string JSON, decode dulu
                                $newDescriptor = is_string($arguments['face_features'])
                                    ? json_decode($arguments['face_features'], true)
                                    : $arguments['face_features'];

                                $existingFeatures = is_array($visitor->face_features) ? $visitor->face_features : [];
                                if (!empty($existingFeatures)) {
                                    $normalized = array_map(fn($e) => is_string($e) ? json_decode($e, true) : $e, $existingFeatures);
                                    $existingFeatures = (isset($normalized[0]) && is_array($normalized[0])) ? $normalized : [$normalized];
                                }

                                if (count($existingFeatures) < 10) {
                                    $existingFeatures[] = $newDescriptor;
                                    $visitor->face_features = $existingFeatures;
                                }
                            }

                            if (!empty($arguments['face_photo'])) {
                                $existingPhotos = is_array($visitor->face_photo) ? $visitor->face_photo : ($visitor->face_photo ? [$visitor->face_photo] : []);
                                if (count($existingPhotos) < 10) {
                                    $existingPhotos[] = $arguments['face_photo'];
                                    $visitor->face_photo = $existingPhotos;
                                }
                            }

                            $visitor->save();
                        }

                        // Proses check-in (admin = manual)
                        $record->update([
                            'status'      => 'active',
                            'checkin_time' => now()->format('H:i'),
                        ]);

                        Notification::make()
                            ->title('Wajah Terdaftar & Check-in Berhasil')
                            ->body("Wajah {$record->visitor->name} telah direkam. Tamu memasuki area.")
                            ->success()->send();
                    }),

                // ─────────────────────────────────────────────────────────────
                // Tombol "Approve" (PIC menyetujui janji temu)
                // ─────────────────────────────────────────────────────────────
                Action::make('approve_appointment')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn(?Appointment $record): bool =>
                        $record?->status === 'pending'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Janji Temu')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui janji temu ini? Pengunjung akan otomatis menerima notifikasi WhatsApp.')
                    ->action(function (Appointment $record) {
                        $record->update([
                            'status'      => 'approved',
                            'approved_at' => now(),
                        ]);

                        if (!empty($record->visitor->phone)) {
                            $msg = "Halo *{$record->visitor->name}*,\n\n";
                            $msg .= "Kunjungan Anda telah *DISETUJUI* ✅ dengan detail berikut:\n";
                            $msg .= "🏢 Menemui: {$record->pic->name}\n";
                            $msg .= "📅 Tanggal: " . \Carbon\Carbon::parse($record->visit_date)->translatedFormat('d F Y') . "\n";
                            $msg .= "⏰ Waktu: " . \Carbon\Carbon::parse($record->visit_time)->format('H:i') . " WIB\n";
                            $msg .= "📝 Keperluan: {$record->purpose}\n\n";
                            $msg .= "Silakan gunakan layar Kiosk (Menu Check-in) di Lobby saat Anda tiba.\n\n";
                            $msg .= "Salam hangat,\nResepsionis VISITA";
                            \App\Helpers\FonnteHelper::sendMessage($record->visitor->phone, $msg, 9);
                        }

                        Notification::make()
                            ->title('Janji Temu Disetujui')
                            ->body("Notifikasi WhatsApp persetujuan telah dikirimkan ke tamu.")
                            ->success()->send();
                    }),

                // ─────────────────────────────────────────────────────────────
                // Tombol "Reject" (PIC menolak janji temu)
                // ─────────────────────────────────────────────────────────────
                Action::make('reject_appointment')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(?Appointment $record): bool =>
                        $record?->status === 'pending'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Janji Temu')
                    ->modalDescription('Apakah Anda yakin ingin menolak janji temu ini? Pengunjung akan menerima notifikasi penolakan via WhatsApp.')
                    ->action(function (Appointment $record) {
                        $record->update([
                            'status'      => 'rejected',
                            'rejected_at' => now(),
                        ]);

                        if (!empty($record->visitor->phone)) {
                            $msg = "Halo *{$record->visitor->name}*,\n\n";
                            $msg .= "Mohon maaf, permintaan kunjungan Anda terpaksa *DITOLAK* ❌ dengan detail:\n";
                            $msg .= "🏢 Menemui: {$record->pic->name}\n";
                            $msg .= "📅 Tanggal: " . \Carbon\Carbon::parse($record->visit_date)->translatedFormat('d F Y') . "\n";
                            $msg .= "⏰ Waktu: " . \Carbon\Carbon::parse($record->visit_time)->format('H:i') . " WIB\n";
                            $msg .= "📝 Keperluan: {$record->purpose}\n\n";
                            $msg .= "Alasan: PIC saat ini sedang tidak dapat ditemui. Mohon berkenan untuk menghubungi PIC Anda secara langsung guna mengatur ulang jadwal pertemuan (reschedule) di waktu yang lebih tepat.\n\n";
                            $msg .= "Salam hangat,\nResepsionis VISITA";
                            \App\Helpers\FonnteHelper::sendMessage($record->visitor->phone, $msg, 9);
                        }

                        Notification::make()
                            ->title('Janji Temu Ditolak')
                            ->body("Data telah dipindahkan ke tabel Summary dan WhatsApp notifikasi dikirimkan.")
                            ->success()->send();
                    }),

                // ─────────────────────────────────────────────────────────────
                // 1b. Tombol "Check In" (tanpa face scan)
                //     Muncul HANYA jika visitor sudah punya face_features.
                //     Admin langsung klik — tidak perlu rekognisi wajah.
                // ─────────────────────────────────────────────────────────────
                Action::make('check_in')
                    ->label('Check In')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('success')
                    ->visible(fn(?Appointment $record): bool =>
                        in_array($record?->status, ['pending', 'approved']) &&
                        !empty($record?->visitor?->face_features)
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Check-in')
                    ->modalDescription(fn(Appointment $record) =>
                        "Check-in tamu {$record->visitor?->name}? Waktu check-in akan dicatat sekarang."
                    )
                    ->action(function (Appointment $record) {
                        // Validasi waktu untuk tipe appointment
                        if ($record->type === 'appointment') {
                            $now = Carbon::now();
                            $scheduledDate = Carbon::parse($record->visit_date);
                            $scheduledTime = $record->should_book_room
                                ? $record->room_start_time
                                : $record->visit_time;
                            $scheduledDateTime = $scheduledDate->setTimeFromTimeString($scheduledTime);
                            $checkInStart = $scheduledDateTime->copy()->subHour();

                            if ($now->toDateString() !== $scheduledDate->toDateString()) {
                                Notification::make()
                                    ->title('Belum Waktunya Check-in!')
                                    ->body("Check-in hanya bisa dilakukan pada tanggal kunjungan ({$scheduledDate->format('d/m/Y')}).")
                                    ->danger()->send();
                                return;
                            }

                            if ($now->isBefore($checkInStart)) {
                                $remaining = $now->diffInMinutes($checkInStart);
                                Notification::make()
                                    ->title('Belum Waktunya Check-in!')
                                    ->body("Check-in dimulai pukul {$checkInStart->format('H:i')} ({$remaining} menit lagi).")
                                    ->danger()->send();
                                return;
                            }
                        }

                        $record->update([
                            'status'      => 'active',
                            'checkin_time' => now()->format('H:i'),
                        ]);

                        Notification::make()
                            ->title('Berhasil Check-in')
                            ->body("Tamu {$record->visitor->name} telah memasuki area.")
                            ->success()->send();
                    }),

                // ─────────────────────────────────────────────────────────────
                // 2. Tombol Check-Out (admin — langsung, tanpa face scan)
                // ─────────────────────────────────────────────────────────────
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
                            'status'          => 'completed',
                            'checkout_time'   => now()->format('H:i'),
                            'checkout_method' => 'manual', // Admin yang menjalankan
                        ]);

                        Notification::make()
                            ->title('Berhasil Check-out')
                            ->body("Kunjungan {$record->visitor->name} telah selesai.")
                            ->success()->send();
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

                // 4b. Tombol Lihat Foto Wajah (hanya muncul jika ada face_photo)
                Action::make('view_face_photo')
                    ->label('')
                    ->icon('heroicon-o-face-smile')
                    ->tooltip('Lihat Foto Wajah')
                    ->color('warning')
                    ->visible(fn(?Appointment $record): bool => !empty($record?->visitor?->face_photo))
                    ->url(fn(Appointment $record): string => route('admin.visitor.face-photo', $record->visitor_id))
                    ->openUrlInNewTab(),

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
