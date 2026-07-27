<?php

namespace App\Filament\Resources\PicAttendances;

use App\Filament\Resources\PicAttendances\Pages\ListPicAttendances;
use App\Filament\Resources\PicAttendances\Tables\PicAttendancesTable;
use App\Models\PicAttendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PicAttendanceResource extends Resource
{
    protected static ?string $model = PicAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = 'Absensi PIC';

    protected static ?string $pluralModelLabel = 'Absensi PIC';

    protected static ?string $navigationLabel = 'Absensi PIC';

    protected static \UnitEnum|string|null $navigationGroup = 'Janji Temu dan PIC';

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return PicAttendancesTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->pic) {
            $query->where('pic_id', $currentUser->pic->id);
        }
        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPicAttendances::route('/'),
        ];
    }
}
