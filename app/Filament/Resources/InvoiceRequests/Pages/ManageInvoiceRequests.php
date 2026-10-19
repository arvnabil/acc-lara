<?php

namespace App\Filament\Resources\InvoiceRequests\Pages;

use App\Filament\Resources\InvoiceRequests\InvoiceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoiceRequests extends ManageRecords
{
    protected static string $resource = InvoiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
