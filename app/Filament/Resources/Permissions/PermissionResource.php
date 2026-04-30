<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ManagePermissions;
use App\Models\Permission;
use App\Models\Menu;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class PermissionResource extends Resource
{
    protected static \UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?int $navigationSort = 3;
    protected static ?string $model = Permission::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';
    protected static ?string $slug = 'permissions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('menu_id')
                    ->label('Menu / Module')
                    ->options(Menu::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->label('Permission Name')
                    ->helperText('Format: menu view, menu edit, halaman fitur')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('guard_name')
                    ->label('Guard Name')
                    ->required()
                    ->default('web'),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->helperText('Penjelasan tentang permission ini'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('menu.name')
                    ->label('Menu / Modul')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Permission Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()->modal(),
                DeleteAction::make(),
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
            'index' => ManagePermissions::route('/'),
        ];
    }
}
