<?php

namespace App\Filament\Resources\InvoiceRequests\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

class InvoiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')->label('Nomor Pesanan')->formatStateUsing(fn ($record): ?string => $record?->order?->number)->disabled(),
                TextInput::make('client_name')->label('Client')->formatStateUsing(fn ($record): ?string => $record?->client?->name)->disabled(),
                TextInput::make('npwp')->label('NPWP')->formatStateUsing(fn ($record): ?string => $record?->taxProfile?->npwp)->disabled(),
                TextInput::make('taxpayer_name')->label('Nama Wajib Pajak')->formatStateUsing(fn ($record): ?string => $record?->taxProfile?->taxpayer_name)->disabled(),
                Select::make('status')->options(['requested' => 'Diajukan', 'processing' => 'Diproses', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])->required(),
                Textarea::make('notes')->label('Catatan Admin')->rows(4)->columnSpanFull(),
                FileUpload::make('invoice_path')->label('Upload Faktur')->disk('public')->directory('invoices')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->downloadable()->openable()->columnSpanFull(),
            ]);
    }
}
