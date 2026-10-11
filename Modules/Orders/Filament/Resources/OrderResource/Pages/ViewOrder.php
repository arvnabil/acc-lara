<?php

namespace Modules\Orders\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Orders\Filament\Resources\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
}
