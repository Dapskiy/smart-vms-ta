<?php

namespace App\Filament\Resources\Summaries;

use App\Filament\Resources\Summaries\Pages;
use App\Models\Appointment;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class SummaryResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static UnitEnum|string|null $navigationGroup = 'Janji Temu dan PIC';
    protected static ?string $navigationLabel = 'Ringkasan';
    protected static ?string $pluralModelLabel = 'Ringkasan Kunjungan';
    protected static ?int $navigationSort = 4; // Positioning di bawah Room Master

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('visit_date')
                    ->label('Tanggal Berkunjung')
                    ->getStateUsing(function (Appointment $record) {
                        if (!$record->visit_date) return '-';
                        return \Carbon\Carbon::parse($record->visit_date)->format('d M Y');
                    })
                    ->sortable(),

                TextColumn::make('visit_time')
                    ->label('Checkin')
                    ->getStateUsing(function (Appointment $record) {
                        if (!$record->visit_time) return '-';
                        $time = $record->visit_time;
                        return is_string($time)
                            ? \Carbon\Carbon::parse($time)->format('H:i')
                            : $time->format('H:i');
                    }),

                TextColumn::make('checkout_time')
                    ->label('Checkout')
                    ->badge()
                    ->color(function (Appointment $record) {
                        if ($record->checkout_method === 'system') {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->getStateUsing(function (Appointment $record) {
                        $timeStr = '-';
                        if ($record->checkout_time) {
                            $time = $record->checkout_time;
                            $timeStr = is_string($time)
                                ? \Carbon\Carbon::parse($time)->format('H:i')
                                : $time->format('H:i');
                        } else {
                            $timeStr = \Carbon\Carbon::parse($record->updated_at)->format('H:i');
                        }

                        if ($record->checkout_method === 'system') {
                            return $timeStr . ' (Auto)';
                        }
                        return $timeStr;
                    }),

                TextColumn::make('visitor.name')
                    ->label('Nama Visitor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('visitor.company')
                    ->label('Instansi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pic.name')
                    ->label('PIC')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('Keperluan')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable()
                    ->wrap(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // View Detail
                Action::make('view_detail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Lihat Detail')
                    ->color('info')
                    ->modalHeading(fn(Appointment $record) => 'Detail Pengunjung: ' . ($record->visitor?->name ?? '-'))
                    ->modalContent(function (Appointment $record) {
                        return view('filament.appointments.detail-modal', ['record' => $record]);
                    })
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                // Edit
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit'),

                // Delete
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSummaries::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['visitor', 'pic'])
            ->whereIn('status', ['completed', 'checkout', 'inactive', 'rejected']);
            
        $currentUser = auth()->user();
        if ($currentUser && !$currentUser->hasRole('super_admin') && $currentUser->pic) {
            $query->where('pic_id', $currentUser->pic->id);
        }

        $type = request('type', empty(request()->all()) ? 'range' : request('type'));

        if ($type === 'range') {
            if (request()->filled('start_date') && request()->filled('end_date')) {
                $from = \Carbon\Carbon::parse(request('start_date'))->startOfDay();
                $to = \Carbon\Carbon::parse(request('end_date'))->endOfDay();
                $query->whereBetween('updated_at', [$from, $to]);
            } elseif (request()->filled('start_date')) {
                $from = \Carbon\Carbon::parse(request('start_date'))->startOfDay();
                $query->where('updated_at', '>=', $from);
            } elseif (request()->filled('end_date')) {
                $to = \Carbon\Carbon::parse(request('end_date'))->endOfDay();
                $query->where('updated_at', '<=', $to);
            }
        } elseif ($type === 'month' && request()->filled('month') && request()->filled('year')) {
            $month = request('month');
            $year = request('year');
            $query->whereMonth('updated_at', $month)->whereYear('updated_at', $year);
        } elseif ($type === 'year' && request()->filled('year')) {
            $year = request('year');
            $query->whereYear('updated_at', $year);
        }

        return $query;
    }
}
