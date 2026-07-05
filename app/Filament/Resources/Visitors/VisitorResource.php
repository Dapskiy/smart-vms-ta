<?php

namespace App\Filament\Resources\Visitors;

use App\Filament\Resources\Visitors\Pages\CreateVisitor;
use App\Filament\Resources\Visitors\Pages\EditVisitor;
use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Schemas\VisitorForm;
use App\Filament\Resources\Visitors\Tables\VisitorsTable;
use App\Models\Visitor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'Visitor';

    protected static ?string $pluralModelLabel = 'Manajemen Visitor';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Data Master';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return VisitorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->pic) {
            $query->whereHas('appointments', function ($q) use ($currentUser) {
                $q->where('pic_id', $currentUser->pic->id);
            });
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVisitors::route('/'),
            'create' => CreateVisitor::route('/create'),
            'edit'   => EditVisitor::route('/{record}/edit'),
        ];
    }
}
