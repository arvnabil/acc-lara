<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Catalog\Filament\Resources\ProductResource;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
