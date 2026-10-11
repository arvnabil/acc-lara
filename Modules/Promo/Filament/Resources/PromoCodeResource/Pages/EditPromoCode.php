<?php

namespace Modules\Promo\Filament\Resources\PromoCodeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Promo\Filament\Resources\PromoCodeResource;

class EditPromoCode extends EditRecord
{
    protected static string $resource = PromoCodeResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
