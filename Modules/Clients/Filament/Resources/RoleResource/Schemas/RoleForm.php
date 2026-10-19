<?php

namespace Modules\Clients\Filament\Resources\RoleResource\Schemas;

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
use Modules\Clients\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function schema(Schema $form): Schema
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
}




