<?php

namespace Modules\Promo\Filament\Resources\PromoCodeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Promo\Filament\Resources\PromoCodeResource;

class ListPromoCodes extends ListRecords
{
    protected static string $resource = PromoCodeResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
