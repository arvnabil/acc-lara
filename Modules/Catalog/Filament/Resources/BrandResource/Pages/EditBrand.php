<?php

namespace Modules\Catalog\Filament\Resources\BrandResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Catalog\Filament\Resources\BrandResource;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
