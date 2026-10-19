<?php

namespace App\Filament\Resources\InvoiceRequests\Pages;

use App\Filament\Resources\InvoiceRequests\InvoiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceRequest extends CreateRecord
{
    protected static string $resource = InvoiceRequestResource::class;
}
