<?php

namespace Modules\Loyalty\Filament\Resources\PointLedgerResource\Schemas;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\PointLedgerResource\Pages;
use Modules\Loyalty\Models\PointLedger;

class PointLedgerForm
{
    public static function schema(Schema $form): Schema { return $form->schema([]); }
}


