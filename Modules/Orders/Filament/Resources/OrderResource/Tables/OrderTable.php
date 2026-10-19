<?php

namespace Modules\Orders\Filament\Resources\OrderResource\Tables;

use Filament\Actions;
use Filament\Forms\Components\Select;
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

class OrderTable
{
    public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('grand_total')->numeric(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('check_midtrans')->label('Cek status Midtrans')->icon('heroicon-o-arrow-path')->visible(fn (Order $record): bool => $record->payment_method === 'midtrans' && $record->status === OrderStatus::PendingPayment && auth()->user()?->can('check_payment'))->action(function (Order $record): void {
                    $transaction = $record->paymentTransactions()->where('provider', 'midtrans')->latest('id')->firstOrFail();
                    app(\Modules\Payments\Services\MidtransPaymentService::class)->checkStatus($transaction);
                }),
                Actions\Action::make('verify_payment')->label('Verify Payment')->visible(fn (Order $record): bool => in_array($record->payment_method, ['qris', 'bank_transfer'], true) && $record->status === OrderStatus::AwaitingVerification && auth()->user()?->can('verify_payment_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Paid, auth()->id())),
                Actions\Action::make('process')->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid && auth()->user()?->can('process_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Processing, auth()->id())),
                Actions\Action::make('pack')->visible(fn (Order $record): bool => $record->status === OrderStatus::Processing && auth()->user()?->can('pack_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Packed, auth()->id())),
                Actions\Action::make('ship')->visible(fn (Order $record): bool => $record->status === OrderStatus::Packed && auth()->user()?->can('ship_order'))->form([TextInput::make('tracking_number')->required()])->action(fn (Order $record, array $data) => app(OrderStateMachine::class)->transition($record, OrderStatus::Shipped, auth()->id(), ['tracking_number' => $data['tracking_number']])),
                Actions\Action::make('edit_tracking')
                    ->label('Edit Resi')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Shipped, OrderStatus::Completed], true) && auth()->user()?->can('ship_order'))
                    ->fillForm(fn (Order $record): array => [
                        'tracking_number' => $record->tracking_number,
                        'biteship_courier_code' => $record->biteship_courier_code,
                    ])
                    ->form([
                        TextInput::make('tracking_number')->label('Nomor Resi')->required()->maxLength(100),
                        Select::make('biteship_courier_code')
                            ->label('Kurir Biteship')
                            ->options([
                                'jne' => 'JNE',
                                'jnt' => 'J&T',
                                'sicepat' => 'SiCepat',
                                'tiki' => 'TIKI',
                                'pos' => 'POS Indonesia',
                                'ninja' => 'Ninja Xpress',
                                'anteraja' => 'AnterAja',
                            ])
                            ->searchable()
                            ->nullable()
                            ->helperText('Wajib dipilih agar riwayat tracking Biteship dapat dicari.'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'tracking_number' => $data['tracking_number'],
                            'biteship_waybill_id' => $data['tracking_number'],
                            'biteship_courier_code' => $data['biteship_courier_code'] ?? null,
                            'biteship_tracking_id' => null,
                            'biteship_tracking_history' => null,
                            'biteship_last_synced_at' => null,
                        ]);
                    }),
                Actions\Action::make('complete')->visible(fn (Order $record): bool => $record->status === OrderStatus::Shipped && auth()->user()?->can('complete_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Completed, auth()->id())),
                Actions\Action::make('cancel')->color('danger')->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::PendingPayment, OrderStatus::AwaitingVerification, OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Packed], true) && auth()->user()?->can('cancel_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Cancelled, auth()->id())),
                Actions\Action::make('return')->color('warning')->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Shipped, OrderStatus::Completed], true) && auth()->user()?->can('return_order'))->action(fn (Order $record) => app(OrderStateMachine::class)->transition($record, OrderStatus::Returned, auth()->id())),
            ])->defaultSort('created_at', 'desc');
        }
}

