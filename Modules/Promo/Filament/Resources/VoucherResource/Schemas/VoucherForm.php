<?php

namespace Modules\Promo\Filament\Resources\VoucherResource\Schemas;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promo\Filament\Resources\VoucherResource\Pages;
use Modules\Promo\Models\Voucher;

class VoucherForm
{
    public static function schema(Schema $form): Schema { return $form->schema([]); }
}


