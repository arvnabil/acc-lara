<?php
namespace Modules\Orders\Filament\Resources\OrderResource\Pages;
use Filament\Resources\Pages\ListRecords;
use Modules\Orders\Filament\Resources\OrderResource;
class ListOrders extends ListRecords { protected static string $resource = OrderResource::class; }
