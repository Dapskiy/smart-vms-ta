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
                TextColumn::make('visitor_display')
                    ->label('Nama Pengunjung')
                    ->html()
                    ->formatStateUsing(function (Appointment $record) {
                        $remaining = $record->remaining_visitors;
                        
                        if (empty($remaining)) {
                            return '<span class="text-gray-500">-</span>';
                        }
                        
                        if (count($remaining) === 1) {
                            return htmlspecialchars($remaining[0]);
                        }
                        
                        $appointmentId = $record->id;
                        $mainName = htmlspecialchars($remaining[0]);
                        $othersCount = count($remaining) - 1;
                        $allNamesJson = json_encode($remaining);
                        $allNamesList = implode('<br>', array_map('htmlspecialchars', $remaining));
                        
                        return <<<HTML
<div class="visitor-list-container" data-id="$appointmentId">
    <span class="visitor-main-name">$mainName</span>
    <button 
        type="button"
        class="visitor-expand-btn"
        data-appointment-id="$appointmentId"
        data-others-count="$othersCount"
        onclick="toggleVisitorList(this, event)"
        style="background: none; border: none; padding: 0; margin-left: 4px; color: #2563eb; cursor: pointer; font-size: 0.875rem; font-weight: 500;">
        + $othersCount others
    </button>
    <div class="visitor-list-expanded hidden" data-appointment-id="$appointmentId" style="margin-top: 8px; padding: 8px; background: #f9fafb; border-radius: 4px; border-left: 3px solid #2563eb;">
        $allNamesList
    </div>
</div>

<script>
if (!window.toggleVisitorList) {
    window.toggleVisitorList = function(btn, event) {
        event.preventDefault();
        const appointmentId = btn.getAttribute('data-appointment-id');
        const expandedDiv = document.querySelector(`.visitor-list-expanded[data-appointment-id="\${appointmentId}"]`);
        
        if (expandedDiv) {
            const isHidden = expandedDiv.classList.contains('hidden');
            
            if (isHidden) {
                expandedDiv.classList.remove('hidden');
                btn.textContent = 'show less';
                btn.style.color = '#1d4ed8';
            } else {
                expandedDiv.classList.add('hidden');
                const othersCount = btn.getAttribute('data-others-count');
                btn.textContent = '+ ' + othersCount + ' others';
                btn.style.color = '#2563eb';
            }
        }
    };
}
</script>
HTML;
                    })
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
                    // PERBAIKAN 2: Tambahkan ? pada Appointment dan ?-> pada pemanggilan status
                    ->visible(fn(?Appointment $record) => $record?->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Check-in')
                    ->modalDescription('Apakah tamu ini sudah tiba di lokasi dan ingin di-check-in?')
                    ->action(function (Appointment $record) {
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
                    // PERBAIKAN 3: Sama seperti di atas, gunakan parameter nullable
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
