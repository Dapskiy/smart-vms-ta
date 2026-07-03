<?php

namespace App\Filament\Resources\Pics\Tables;

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
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                ToggleColumn::make('is_available')
                    ->label('Status Hadir'),
                IconColumn::make('face_registered')
                    ->label('Face ID')
                    ->boolean()
                    ->state(fn ($record) => !empty($record->face_features) && is_array($record->face_features) && count($record->face_features) > 0),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('register_face')
                    ->label('Scan Wajah')
                    ->icon('heroicon-o-camera')
                    ->color('info')
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
