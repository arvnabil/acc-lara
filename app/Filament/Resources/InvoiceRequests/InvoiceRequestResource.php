<?php

namespace App\Filament\Resources\InvoiceRequests;

use App\Filament\Resources\InvoiceRequests\Pages\CreateInvoiceRequest;
use App\Filament\Resources\InvoiceRequests\Pages\EditInvoiceRequest;
use App\Filament\Resources\InvoiceRequests\Pages\ListInvoiceRequests;
use App\Filament\Resources\InvoiceRequests\Schemas\InvoiceRequestForm;
use App\Filament\Resources\InvoiceRequests\Tables\InvoiceRequestsTable;
use App\Models\InvoiceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InvoiceRequestResource extends Resource
{
    protected static ?string $model = InvoiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'status';
    protected static ?string $navigationLabel = 'Request Faktur';
    protected static ?string $modelLabel = 'Request Faktur';
    protected static ?string $pluralModelLabel = 'Request Faktur';
    protected static string|\UnitEnum|null $navigationGroup = 'Orders';

    public static function form(Schema $schema): Schema
    {
        return InvoiceRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['order', 'client', 'taxProfile']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoiceRequests::route('/'),
            'edit' => EditInvoiceRequest::route('/{record}/edit'),
        ];
    }
}
