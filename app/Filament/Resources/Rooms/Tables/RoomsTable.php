<?php

namespace App\Filament\Resources\Rooms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Ruangan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('current_status')
                    ->label('Status Saat Ini')
                    ->badge()
                    ->getStateUsing(function (\App\Models\Room $record) {
                        $now = now();
                        $currentTime = $now->format('H:i');
                        $today = $now->toDateString();

                        $activeAppointment = $record->appointments()
                            ->where('visit_date', $today)
                            ->where('should_book_room', true)
                            ->where('room_start_time', '<=', $currentTime)
                            ->where('room_end_time', '>', $currentTime)
                            ->whereNotIn('status', ['cancelled', 'completed', 'checkout', 'inactive'])
                            ->first();

                        if ($activeAppointment) {
                            $start = \Carbon\Carbon::parse($activeAppointment->room_start_time)->format('H:i');
                            $end   = \Carbon\Carbon::parse($activeAppointment->room_end_time)->format('H:i');
                            return "Dipakai ({$start} - {$end})";
                        }

                        return 'Tersedia';
                    })
                    ->color(fn (string $state): string => str_contains($state, 'Dipakai') ? 'danger' : 'success'),

                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
