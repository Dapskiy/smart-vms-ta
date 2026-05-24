<?php

namespace App\Filament\Resources\Visitors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->color(fn (string $state): string => match ($state) {
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
                    ->trueIcon('heroicon-o-face-smile')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->action(
                        \Filament\Actions\Action::make('view_face')
                            ->url(fn ($record): string => route('admin.visitor.face-photo', $record->id))
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record->face_photo))
                    ),

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
