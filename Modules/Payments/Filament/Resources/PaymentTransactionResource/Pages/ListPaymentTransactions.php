<?php

namespace Modules\Payments\Filament\Resources\PaymentTransactionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Payments\Filament\Resources\PaymentTransactionResource;

class ListPaymentTransactions extends ListRecords
{
    protected static string $resource = PaymentTransactionResource::class;
}
