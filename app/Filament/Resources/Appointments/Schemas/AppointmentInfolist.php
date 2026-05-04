<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([

                // === KIRI: Informasi Tamu ===
                Section::make('Informasi Tamu')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('visitor.name')
                            ->label('Nama Tamu'),

                        TextEntry::make('visitor.company')
                            ->label('Instansi / Perusahaan')
                            ->placeholder('—'),

                        TextEntry::make('visitor.phone')
                            ->label('No. WhatsApp')
                            ->placeholder('—'),

                        TextEntry::make('visitor.email')
                            ->label('Email')
                            ->placeholder('—'),

                        TextEntry::make('visitor.identity_type')
                            ->label('Jenis Identitas')
                            ->placeholder('—'),

                        TextEntry::make('visitor.identity_number')
                            ->label('No. Identitas')
                            ->placeholder('—'),

                        TextEntry::make('visitor.is_blacklisted')
                            ->label('Status Blacklist')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state ? 'Blacklisted' : 'Clear')
                            ->color(fn ($state): string => $state ? 'danger' : 'success'),
                    ]),

                // === KANAN: Detail Kunjungan ===
                Section::make('Detail Kunjungan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextEntry::make('type')
                            ->label('Tipe Kunjungan')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'appointment' => 'success',
                                'walk-in'     => 'warning',
                                default       => 'gray',
                            }),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'pending'   => 'Menunggu',
                                'active'    => 'Di dalam',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default     => $state ?? '—',
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'pending'   => 'warning',
                                'active'    => 'success',
                                'completed' => 'gray',
                                'cancelled' => 'danger',
                                default     => 'gray',
                            }),

                        TextEntry::make('purpose')
                            ->label('Tujuan / Perihal')
                            ->columnSpanFull(),

                        TextEntry::make('visit_date')
                            ->label('Tanggal Kunjungan')
                            ->date('d/m/Y'),

                        TextEntry::make('visit_time')
                            ->label('Jam Kunjungan')
                            ->time('H:i'),

                        TextEntry::make('pax')
                            ->label('Total Orang'),

                        TextEntry::make('vehicle_number')
                            ->label('Plat Nomor')
                            ->placeholder('Tidak ada'),

                        TextEntry::make('token')
                            ->label('Token Undangan')
                            ->copyable()
                            ->copyMessage('Token berhasil disalin!'),

                        TextEntry::make('pic.name')
                            ->label('PIC / Penanggung Jawab'),

                        TextEntry::make('room.name')
                            ->label('Ruang Meeting')
                            ->placeholder('Tidak ada booking ruangan'),
                    ]),
            ]),

            // === BAWAH: Anggota Rombongan (hanya tampil jika ada) ===
            Section::make('Anggota Rombongan')
                ->icon('heroicon-o-user-group')
                ->schema([
                    RepeatableEntry::make('companions')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('name')
                                ->label('Nama Anggota'),
                        ])
                        ->columns(3),
                ])
                ->hidden(fn ($record) => empty($record?->companions)),
        ]);
    }
}
