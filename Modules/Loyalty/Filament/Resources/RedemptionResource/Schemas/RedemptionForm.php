<?php

namespace Modules\Loyalty\Filament\Resources\RedemptionResource\Schemas;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\RedemptionResource\Pages;
use Modules\Loyalty\Models\Redemption;
use Modules\Loyalty\Services\LoyaltyService;

class RedemptionForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([]);
        }
}


