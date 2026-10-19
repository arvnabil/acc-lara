<?php

namespace App\Filament\Resources\InvoiceRequests\Pages;

use App\Filament\Resources\InvoiceRequests\InvoiceRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceRequest extends EditRecord
{
    protected static string $resource = InvoiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
