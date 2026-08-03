<?php

namespace App\Filament\Resources\KioskLocations;

use App\Filament\Resources\KioskLocations\Pages\CreateKioskLocation;
use App\Filament\Resources\KioskLocations\Pages\EditKioskLocation;
use App\Filament\Resources\KioskLocations\Pages\ListKioskLocations;
use App\Filament\Resources\KioskLocations\Schemas\KioskLocationForm;
use App\Filament\Resources\KioskLocations\Tables\KioskLocationsTable;
use App\Models\KioskLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KioskLocationResource extends Resource
{
    protected static ?string $model = KioskLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'Janji Temu dan PIC';
    protected static ?string $navigationLabel = 'Gedung Front Office';
    protected static ?string $modelLabel = 'Gedung Front Office';
    protected static ?string $pluralModelLabel = 'Gedung Front Office';

    public static function form(Schema $schema): Schema
    {
        return KioskLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KioskLocationsTable::configure($table);
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
            'index' => ListKioskLocations::route('/'),
            'create' => CreateKioskLocation::route('/create'),
            'edit' => EditKioskLocation::route('/{record}/edit'),
        ];
    }
}
