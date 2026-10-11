<?php

namespace Modules\Accounts\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accounts\Filament\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->selectedPermissions());
    }

    private function selectedPermissions(): array
    {
        $state = $this->form->getRawState();

        return collect($state)->filter(fn ($value, $key): bool => str_starts_with($key, 'permissions_'))
            ->flatten()->unique()->values()->all();
    }
}
