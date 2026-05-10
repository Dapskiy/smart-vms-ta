<?php

namespace App\Filament\Resources\Pics;

use App\Filament\Resources\Pics\Pages\CreatePic;
use App\Filament\Resources\Pics\Pages\EditPic;
use App\Filament\Resources\Pics\Pages\ListPics;
use App\Filament\Resources\Pics\Schemas\PicForm;
use App\Filament\Resources\Pics\Tables\PicsTable;
use App\Models\Pic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PicResource extends Resource
{
    protected static ?string $model = Pic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PicForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicsTable::configure($table);
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
            'index' => ListPics::route('/'),
            'create' => CreatePic::route('/create'),
            'edit' => EditPic::route('/{record}/edit'),
        ];
    }
}
