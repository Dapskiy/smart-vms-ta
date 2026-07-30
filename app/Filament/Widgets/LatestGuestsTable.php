<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Appointment;

class LatestGuestsTable extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query(function () {
                $query = Appointment::query()->with('visitor')->latest();
                $currentUser = auth()->user();
                if ($currentUser && !$currentUser->hasRole('super_admin') && $currentUser->pic) {
                    $query->where('pic_id', $currentUser->pic->id);
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('visitor.name')
                    ->label('NAMA TAMU')
                    ->searchable()
                    ->placeholder('Arya Santoso'), // Dummy fallback
                Tables\Columns\TextColumn::make('purpose')
                    ->label('TUJUAN')
                    ->placeholder('Wawancara HR'), // Dummy fallback
                Tables\Columns\TextColumn::make('visit_time')
                    ->label('MASUK')
                    ->getStateUsing(function ($record) {
                        if (!$record->visit_time) {
                            return '-';
                        }
                        
                        $time = $record->visit_time;
                        return is_string($time)
                            ? \Carbon\Carbon::parse($time)->format('H:i')
                            : $time->format('H:i');
                    })
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
                Action::make('detail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Lihat Detail')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Detail Pengunjung: ' . ($record->visitor?->name ?? 'Tamu'))
                    ->modalContent(fn($record) => view('filament.appointments.detail-modal', ['record' => $record]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->heading('Daftar Tamu')
            ->description('Hari ini');
    }
}
