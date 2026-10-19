<?php

namespace Modules\Orders\Filament\Resources;

use Modules\Orders\Filament\Resources\OrderResource\Schemas\OrderForm;
use Modules\Orders\Filament\Resources\OrderResource\Tables\OrderTable;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Filament\Resources\OrderResource\Pages;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderStateMachine;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Orders';

    public static function form(Schema $form): Schema
    {
        return OrderForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return OrderTable::table($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('number')->label('Order Number'),
            TextEntry::make('status')->badge(),
            TextEntry::make('client.name')->label('Client'),
            TextEntry::make('grand_total')->numeric(),
            TextEntry::make('payment_method')->label('Payment'),
            TextEntry::make('shipping_method')->label('Shipping'),
            TextEntry::make('tracking_number'),
            TextEntry::make('created_at')->dateTime(),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrders::route('/'), 'view' => Pages\ViewOrder::route('/{record}')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_order') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_order') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}


