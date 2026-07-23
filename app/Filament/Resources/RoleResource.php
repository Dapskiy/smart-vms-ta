<?php

namespace App\Filament\Resources;

use App\Models\Role;
use App\Models\Permission;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Illuminate\Support\Str;

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
                        $dashboardWidgets = [
                            'Dashboard',
                            'GuestStatsOverview',
                            'VisitTrendChart',
                            'VisitPurposeChart',
                            'LatestGuestsTable',
                            'AdminAiChatWidget',
                        ];

                        $allPermissions = Permission::with('menu')->get();

                        // Group permissions dynamically by menu or by resource name
                        $groupedPermissions = $allPermissions->groupBy(function ($perm) use ($dashboardWidgets) {
                            if ($perm->menu) {
                                return $perm->menu->name;
                            }
                            if (str_contains($perm->name, ':')) {
                                $resource = explode(':', $perm->name, 2)[1];
                                if (in_array($resource, $dashboardWidgets)) {
                                    return 'Dashboard';
                                }
                                return Str::headline($resource);
                            }
                            return 'Lainnya';
                        })->sortKeys();

                        $sections = [];

                        // Global Centang Semua Checkbox
                        $sections[] = Section::make('Centang Semua')
                            ->contained(false)
                            ->schema([
                                Checkbox::make('select_all_permissions')
                                    ->label('Centang Semua Permission')
                                    ->live()
                                    ->afterStateHydrated(function ($component, $record) use ($allPermissions) {
                                        if (!$record) return;
                                        $allIds = $allPermissions->pluck('id')->toArray();
                                        $roleIds = $record->permissions->pluck('id')->toArray();
                                        if (count($allIds) > 0 && count(array_diff($allIds, $roleIds)) === 0) {
                                            $component->state(true);
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, $set) use ($groupedPermissions) {
                                        foreach ($groupedPermissions as $groupTitle => $permissions) {
                                            $groupKey = 'permissions_group_' . Str::slug($groupTitle, '_');
                                            if ($state) {
                                                $set($groupKey, $permissions->pluck('id')->toArray());
                                            } else {
                                                $set($groupKey, []);
                                            }
                                        }
                                    })
                                    ->dehydrated(false)
                            ])
                            ->columnSpanFull();

                        foreach ($groupedPermissions as $groupTitle => $permissions) {
                            if ($permissions->isEmpty()) continue;

                            $groupKey = 'permissions_group_' . Str::slug($groupTitle, '_');

                            $sections[] = Section::make($groupTitle)
                                ->contained(false) // Menghapus kotak/tabel container
                                ->schema([
                                    CheckboxList::make($groupKey)
                                        ->options(function () use ($permissions) {
                                            return $permissions->mapWithKeys(function (Permission $record) {
                                                // Nama fitur (spasi) nama menu, huruf kecil semua
                                                $label = strtolower(str_replace(':', ' ', $record->name));
                                                return [$record->id => trim($label)];
                                            });
                                        })
                                        ->columns(3)
                                        ->bulkToggleable()
                                        ->hiddenLabel()
                                        ->gridDirection('row')
                                        ->afterStateHydrated(function ($component, $record) use ($permissions) {
                                            if (!$record) return;
                                            $permIds = $permissions->pluck('id')->toArray();
                                            $rolePermIds = $record->permissions->pluck('id')->toArray();
                                            $intersect = array_values(array_intersect($rolePermIds, $permIds));
                                            $component->state($intersect);
                                        })
                                        ->dehydrated(false)
                                ])
                                ->columnSpanFull();
                        }

                        // Hidden field to handle syncing relationships
                        $sections[] = Hidden::make('permissions_sync')
                            ->dehydrated(true)
                            ->saveRelationshipsUsing(function ($record, $state, $get) use ($groupedPermissions) {
                                $ids = [];
                                foreach ($groupedPermissions as $groupTitle => $permissions) {
                                    $groupKey = 'permissions_group_' . Str::slug($groupTitle, '_');
                                    $selectedIds = $get($groupKey);
                                    if (is_array($selectedIds)) {
                                        $ids = array_merge($ids, $selectedIds);
                                    }
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
