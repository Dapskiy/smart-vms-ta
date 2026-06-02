<?php

namespace App\Filament\Resources\Visitors\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('identity_number')
                    ->label('No. Identitas')
                    ->searchable(),

                TextColumn::make('identity_type')
                    ->label('Jenis ID')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'KTP'      => 'info',
                        'SIM'      => 'warning',
                        'Passport' => 'success',
                        default    => 'gray',
                    }),

                TextColumn::make('company')
                    ->label('Instansi')
                    ->searchable()
                    ->default('-'),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),

                IconColumn::make('face_features')
                    ->label('Wajah')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->face_features))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('is_blacklisted')
                    ->label('Blacklist')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),

                TextColumn::make('appointments_count')
                    ->label('Kunjungan')
                    ->counts('appointments')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_blacklisted')
                    ->label('Status Blacklist')
                    ->trueLabel('Di-blacklist')
                    ->falseLabel('Normal')
                    ->placeholder('Semua'),

                TernaryFilter::make('has_face')
                    ->label('Data Wajah')
                    ->trueLabel('Sudah terdaftar')
                    ->falseLabel('Belum terdaftar')
                    ->placeholder('Semua')
                    ->queries(
                        true:  fn ($query) => $query->whereNotNull('face_features'),
                        false: fn ($query) => $query->whereNull('face_features'),
                    ),
            ])
            ->recordActions([
                // Tombol lihat foto wajah — muncul hanya jika visitor punya foto
                Action::make('view_face_photo')
                    ->label('')
                    ->icon('heroicon-o-face-smile')
                    ->color('warning')
                    ->tooltip('Lihat Foto Wajah')
                    ->url(fn ($record): string => route('admin.visitor.face-photo', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => !empty($record->face_photo)),

                // Tombol blacklist/unblacklist dengan konfirmasi wajib ketik "BLACKLIST"
                Action::make('toggle_blacklist')
                    ->label('')
                    ->icon(fn ($record) => $record->is_blacklisted ? 'heroicon-o-lock-open' : 'heroicon-o-no-symbol')
                    ->color(fn ($record) => $record->is_blacklisted ? 'success' : 'danger')
                    ->tooltip(fn ($record) => $record->is_blacklisted ? 'Hapus Blacklist' : 'Blacklist Visitor')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_blacklisted ? 'Hapus Blacklist Visitor' : 'Blacklist Visitor')
                    ->modalDescription(fn ($record) => $record->is_blacklisted
                        ? "Visitor {$record->name} akan dihapus dari daftar blacklist."
                        : "Visitor {$record->name} akan diblacklist dan tidak bisa melakukan kunjungan. Ketik \"BLACKLIST\" untuk konfirmasi."
                    )
                    ->form(fn ($record) => $record->is_blacklisted ? [] : [
                        TextInput::make('confirmation')
                            ->label('Ketik "BLACKLIST" untuk konfirmasi')
                            ->required()
                            ->rules(['in:BLACKLIST'])
                            ->validationMessages(['in' => 'Anda harus mengetik kata "BLACKLIST" dengan tepat.'])
                            ->placeholder('BLACKLIST'),
                    ])
                    ->action(function ($record) {
                        $record->update([
                            'is_blacklisted' => !$record->is_blacklisted,
                        ]);
                        Notification::make()
                            ->title($record->is_blacklisted ? '✅ Blacklist dihapus' : '🚫 Visitor di-blacklist')
                            ->body($record->is_blacklisted
                                ? "{$record->name} dapat melakukan kunjungan kembali."
                                : "{$record->name} tidak dapat melakukan kunjungan."
                            )
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
