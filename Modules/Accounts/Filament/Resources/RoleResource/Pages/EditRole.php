<?php

namespace Modules\Accounts\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Str;
use Modules\Accounts\Filament\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $selected = $this->record->permissions()->pluck('name')->all();
        foreach (app(ModuleManager::class)->enabled() as $module) {
            $key = Str::snake($module->name);
            $selectedForModule = array_values(array_intersect($module->permissions, $selected));
            $data["permissions_{$key}"] = $selectedForModule;
            $data["select_all_{$key}"] = count($selectedForModule) > 0 && count($selectedForModule) === count($module->permissions);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getRawState();
        $permissions = collect($state)->filter(fn ($value, $key): bool => str_starts_with($key, 'permissions_'))
            ->flatten()->unique()->values()->all();
        $this->record->syncPermissions($permissions);
    }

    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
