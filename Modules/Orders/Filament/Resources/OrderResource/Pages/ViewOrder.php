<?php

namespace Modules\Orders\Filament\Resources\OrderResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Orders\Filament\Resources\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadInvoice')
                ->label('Download Invoice PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (): string => route('admin.orders.invoice', ['order' => $this->record]))
                ->openUrlInNewTab(),
        ];
    }
}
