<?php

namespace App\Filament\Resources\FaceVerificationLogs;

use App\Filament\Resources\FaceVerificationLogs\Pages\ListFaceVerificationLogs;
use App\Filament\Resources\FaceVerificationLogs\Pages\ViewFaceVerificationLog;
use App\Filament\Resources\FaceVerificationLogs\Schemas\FaceVerificationLogForm;
use App\Filament\Resources\FaceVerificationLogs\Schemas\FaceVerificationLogInfolist;
use App\Filament\Resources\FaceVerificationLogs\Tables\FaceVerificationLogsTable;
use App\Models\FaceVerificationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FaceVerificationLogResource extends Resource
{
    protected static ?string $model = FaceVerificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Log Verifikasi Wajah';

    protected static ?string $pluralLabel = 'Log Verifikasi Wajah';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    public static function form(Schema $schema): Schema
    {
        return FaceVerificationLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FaceVerificationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaceVerificationLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaceVerificationLogs::route('/'),
            'view' => ViewFaceVerificationLog::route('/{record}'),
        ];
    }
}
