<?php

namespace App\Filament\Resources\PicAttendances\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PicAttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('checked_at', 'desc')
            ->columns([
                TextColumn::make('pic.name')
                    ->label('Nama PIC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'checkin' => 'Check-In',
                        'checkout' => 'Check-Out',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'checkin' => 'success',
                        'checkout' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'kiosk' => 'Kiosk',
                        'manual' => 'Manual',
                        'admin' => 'Admin',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'kiosk' => 'warning',
                        'manual' => 'info',
                        'admin' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('checked_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'checkin' => 'Check-In',
                        'checkout' => 'Check-Out',
                    ]),
                SelectFilter::make('method')
                    ->label('Metode')
                    ->options([
                        'kiosk' => 'Kiosk',
                        'manual' => 'Manual',
                        'admin' => 'Admin',
                    ]),
                SelectFilter::make('pic_id')
                    ->label('PIC')
                    ->relationship('pic', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
