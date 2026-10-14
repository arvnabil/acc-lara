<?php

namespace Modules\Orders\Filament\Resources;

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
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('number')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('grand_total')->numeric(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Actions\ViewAction::make(),
            Actions\Action::make('verify_payment')->label('Verify Payment')->visible(fn (Order $record): bool => $record->status === OrderStatus::AwaitingVerification && auth()->user()?->can('verify_payment_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Paid, auth()->id())),
            Actions\Action::make('process')->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid && auth()->user()?->can('process_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Processing, auth()->id())),
            Actions\Action::make('pack')->visible(fn (Order $record): bool => $record->status === OrderStatus::Processing && auth()->user()?->can('pack_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Packed, auth()->id())),
            Actions\Action::make('ship')->visible(fn (Order $record): bool => $record->status === OrderStatus::Packed && auth()->user()?->can('ship_order'))->form([TextInput::make('tracking_number')->required()])->action(fn (Order $record, array $data) => app(OrderStateMachine::class)->transition($record, OrderStatus::Shipped, auth()->id(), ['tracking_number' => $data['tracking_number']])),
            Actions\Action::make('complete')->visible(fn (Order $record): bool => $record->status === OrderStatus::Shipped && auth()->user()?->can('complete_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Completed, auth()->id())),
            Actions\Action::make('cancel')->color('danger')->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::PendingPayment, OrderStatus::AwaitingVerification, OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Packed], true) && auth()->user()?->can('cancel_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Cancelled, auth()->id())),
            Actions\Action::make('return')->color('warning')->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Shipped, OrderStatus::Completed], true) && auth()->user()?->can('return_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Returned, auth()->id())),
        ])->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('number')->label('Order Number'),
            TextEntry::make('status')->badge(),
            TextEntry::make('company.name')->label('Company'),
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
