<?php

namespace Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Schemas;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Pages;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Services\LoyaltyService;

class LoyaltyAccountForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([]);
        }
}


