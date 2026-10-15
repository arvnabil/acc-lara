<?php

namespace Modules\Orders\Filament\Resources\OrderResource\Schemas;

use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Filament\Resources\OrderResource\Pages;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderStateMachine;

class OrderForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([]);
        }
}


