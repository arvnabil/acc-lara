<?php

namespace App\Filament\Resources\InvoiceRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class InvoiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.number')->label('Pesanan')->searchable(),
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('taxProfile.npwp')->label('NPWP')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('requested_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('invoice_path')->label('Faktur')->formatStateUsing(fn (?string $state): string => $state ? 'Tersedia' : 'Belum diunggah')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
