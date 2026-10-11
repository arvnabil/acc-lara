<?php

namespace Modules\Accounts\Filament\Resources;

use App\Support\Modules\ModuleManager;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Accounts\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static string | \UnitEnum | null $navigationGroup = 'Accounts';
    protected static ?int $navigationSort = 30;

    public static function form(Schema $form): Schema
    {
        $permissionOptions = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name')->all();
        $moduleSections = [];

        foreach (app(ModuleManager::class)->enabled() as $module) {
            $modulePermissions = array_values(array_intersect($module->permissions, array_keys($permissionOptions)));
            if ($modulePermissions === []) {
                continue;
            }

            $moduleKey = Str::snake($module->name);
            $moduleOptions = collect($modulePermissions)
                ->mapWithKeys(fn (string $permission): array => [
                    $permission => Str::headline(str_replace('_', ' ', $permission)),
                ])
                ->all();

            $moduleSections[] = Section::make(Str::headline($module->name))->schema([
                Forms\Components\Toggle::make("select_all_{$moduleKey}")
                    ->label('Select All')
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(function (bool $state, $set) use ($moduleKey, $modulePermissions): void {
                        $set("permissions_{$moduleKey}", $state ? $modulePermissions : []);
                    }),
                Forms\Components\CheckboxList::make("permissions_{$moduleKey}")
                    ->label('Permissions')
                    ->options($moduleOptions)
                    ->columns(1)
                    ->bulkToggleable()
                    ->live()
                    ->afterStateUpdated(function (array $state, $set) use ($moduleKey, $modulePermissions): void {
                        $set("select_all_{$moduleKey}", count($state) > 0 && count($state) === count($modulePermissions));
                    }),
            ])->collapsible();
        }

        return $form->schema([
            Section::make('Role Details')
                ->description('Nama role dan identitas akses panel.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Role Name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record): bool => $record?->name === 'administrator'),
                ])
                ->columnSpanFull(),
            Section::make('Permissions Matrix')
                ->description('Kelola permission per modul. Gunakan Select All pada kartu atau tombol global.')
                ->headerActions([
                    Action::make('selectAllModules')->label('Select All Modules')->icon('heroicon-m-check-badge')->color('success')->button()
                        ->action(function ($set): void {
                            foreach (app(ModuleManager::class)->enabled() as $module) {
                                $key = Str::snake($module->name);
                                $permissions = array_values(array_intersect($module->permissions, Permission::query()->where('guard_name', 'web')->pluck('name')->all()));
                                $set("permissions_{$key}", $permissions);
                                $set("select_all_{$key}", true);
                            }
                        }),
                    Action::make('clearAllModules')->label('Clear All Modules')->icon('heroicon-m-x-circle')->button()
                        ->action(function ($set): void {
                            foreach (app(ModuleManager::class)->enabled() as $module) {
                                $key = Str::snake($module->name);
                                $set("permissions_{$key}", []);
                                $set("select_all_{$key}", false);
                            }
                        }),
                ])
                ->schema([Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema($moduleSections)])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')->counts('permissions')->label('Permissions')->sortable(),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Users')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()->disabled(fn (Role $record): bool => $record->name === 'administrator')])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_role') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_role') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_role') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_role') ?? false; }
    public static function canDelete($record): bool { return $record->name !== 'administrator' && (auth()->user()?->can('delete_role') ?? false); }
    public static function canDeleteAny(): bool { return false; }
}
