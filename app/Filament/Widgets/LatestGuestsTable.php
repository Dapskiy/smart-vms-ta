<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Appointment;

class LatestGuestsTable extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('visitor.name')
                    ->label('NAMA TAMU')
                    ->searchable()
                    ->placeholder('Arya Santoso'), // Dummy fallback
                Tables\Columns\TextColumn::make('purpose')
                    ->label('TUJUAN')
                    ->placeholder('Wawancara HR'), // Dummy fallback
                Tables\Columns\TextColumn::make('expected_arrival_time')
                    ->label('MASUK')
                    ->time('H:i')
                    ->placeholder('08:30'), // Dummy fallback
                Tables\Columns\TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'checked_in' => 'Di dalam',
                        'pending' => 'Menunggu',
                        'approved' => 'Terjadwal',
                        'rejected' => 'Ditolak',
                        'checked_out' => 'Selesai',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'checked_in' => 'success',
                        'pending' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'checked_out' => 'gray',
                        default => 'success',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('Detail')
                    ->url(fn (Appointment $record): string => '#')
                    ->button()
                    ->outlined()
                    ->size(\Filament\Support\Enums\ActionSize::Small),
            ])
            ->heading('Daftar Tamu')
            ->description('Hari ini');
    }
}
