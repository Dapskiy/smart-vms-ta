<?php

namespace App\Filament\Resources\Pics\Tables;

use App\Models\PicAttendance;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class PicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Akun')
                    ->placeholder('Belum terhubung')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                IconColumn::make('is_present')
                    ->label('Status Hadir')
                    ->boolean()
                    ->state(fn ($record) => ($record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first()?->type === 'checkin') && $record->is_available),
                TextColumn::make('current_location')
                    ->label('Lokasi Saat Ini')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
                IconColumn::make('face_registered')
                    ->label('Face ID')
                    ->boolean()
                    ->state(fn ($record) => !empty($record->face_features) && is_array($record->face_features) && count($record->face_features) > 0),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('manual_attendance')
                    ->label(fn ($record) => ($record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first()?->type === 'checkin') ? 'Absen Keluar' : 'Absen Masuk')
                    ->icon(fn ($record) => ($record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first()?->type === 'checkin') ? 'heroicon-o-arrow-right-start-on-rectangle' : 'heroicon-o-arrow-left-end-on-rectangle')
                    ->color(fn ($record) => ($record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first()?->type === 'checkin') ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => (($record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first()?->type === 'checkin') ? 'Absen Keluar Manual' : 'Absen Masuk Manual') . ': ' . $record->name)
                    ->modalDescription(fn ($record) => 'Apakah Anda yakin ingin melakukan absensi ' . (($record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first()?->type === 'checkin') ? 'keluar' : 'masuk') . ' manual untuk ' . $record->name . '?')
                    ->action(function ($record) {
                        $latest = $record->attendances()->whereDate('checked_at', today())->latest('checked_at')->first();
                        $isCheckingIn = ($latest?->type !== 'checkin');

                        $record->is_available = $isCheckingIn;
                        if (!$isCheckingIn) {
                            $record->current_location = null;
                        }
                        $record->save();

                        PicAttendance::create([
                            'pic_id' => $record->id,
                            'type' => $isCheckingIn ? 'checkin' : 'checkout',
                            'method' => 'manual',
                            'checked_at' => now(),
                        ]);

                        $statusText = $isCheckingIn ? 'Masuk' : 'Keluar';
                        \Filament\Notifications\Notification::make()
                            ->title("{$record->name} berhasil Absen {$statusText}!")
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('register_face')
                    ->label('Scan Wajah')
                    ->icon('heroicon-o-camera')
                    ->color('warning')
                    ->modalHeading(fn ($record) => 'Registrasi Wajah: ' . $record->name)
                    ->modalContent(fn ($record) => view('filament.admin.pic-face-scan', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
