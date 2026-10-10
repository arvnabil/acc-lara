<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Catalog\Filament\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
