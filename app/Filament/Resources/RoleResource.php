<?php

namespace App\Filament\Resources;

use App\Models\Role;
use App\Models\Permission;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class RoleResource extends Resource
{
    protected static \UnitEnum|string|null $navigationGroup = 'Konfigurasi';
    protected static ?int $navigationSort = 2;
    protected static ?string $model = Role::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $slug = 'roles';
    protected static ?string $navigationLabel = 'Roles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Role')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('guard_name')
                    ->label('Guard Name')
                    ->default('web')
                    ->hidden(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Section::make('Permissions')
                    ->description('Daftar hak akses berdasarkan modul')
                    ->schema(function () {
                        $menus = \App\Models\Menu::with('permissions')->orderBy('name')->get();
                        $sections = [];

                        foreach ($menus as $menu) {
                            if ($menu->permissions->isEmpty()) continue;

                            $sections[] = Section::make($menu->name)
                                ->compact()
                                ->schema([
                                    CheckboxList::make('permissions_menu_' . $menu->id)
                                        ->options($menu->permissions->pluck('name', 'id'))
                                        ->getOptionLabelFromRecordUsing(function (Permission $record) use ($menu) {
                                            $label = $record->name;
                                            $label = str_ireplace(['[' . $menu->name . '] ', $menu->name . ' ', ':' . $menu->name], '', $label);
                                            return ucwords(trim($label));
                                        })
                                        ->columns(3)
                                        ->label('')
                                        ->gridDirection('row')
                                        ->afterStateHydrated(fn ($component, $record) => $record ? $component->state($record->permissions->where('menu_id', $menu->id)->pluck('id')->toArray()) : null)
                                        ->dehydrated(false)
                                ]);
                        }

                        // Permissions tanpa menu
                        $generalPermissions = \App\Models\Permission::whereNull('menu_id')->get();
                        if ($generalPermissions->isNotEmpty()) {
                            $sections[] = Section::make('Lainnya')
                                ->compact()
                                ->schema([
                                    CheckboxList::make('permissions_general')
                                        ->options($generalPermissions->pluck('name', 'id'))
                                        ->columns(3)
                                        ->label('')
                                        ->afterStateHydrated(fn ($component, $record) => $record ? $component->state($record->permissions->whereNull('menu_id')->pluck('id')->toArray()) : null)
                                        ->dehydrated(false)
                                ]);
                        }

                        // Hidden field to handle syncing
                        $sections[] = Hidden::make('permissions_sync')
                            ->dehydrated(true)
                            ->saveRelationshipsUsing(function ($record, $state, $get) use ($menus, $generalPermissions) {
                                $ids = [];
                                foreach ($menus as $menu) {
                                    $menuIds = $get('permissions_menu_' . $menu->id);
                                    if (is_array($menuIds)) {
                                        $ids = array_merge($ids, $menuIds);
                                    }
                                }
                                
                                $generalIds = $get('permissions_general');
                                if (is_array($generalIds)) {
                                    $ids = array_merge($ids, $generalIds);
                                }

                                $record->permissions()->sync($ids);
                            });

                        return $sections;
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Role')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Jumlah Permissions')
                    ->counts('permissions'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
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
            'index' => RoleResource\Pages\ListRoles::route('/'),
            'create' => RoleResource\Pages\CreateRole::route('/create'),
            'edit' => RoleResource\Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
