<?php

namespace Modules\Payments\Filament\Resources;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Orders\Models\PaymentTransaction;
use Modules\Payments\Filament\Resources\PaymentTransactionResource\Pages;
use Modules\Payments\Services\MidtransPaymentService;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null $navigationGroup = 'Payments';
    protected static ?string $navigationLabel = 'Payment Transactions';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('provider_order_id')->label('Provider Order ID')->searchable()->copyable(),
            TextColumn::make('order.number')->label('Order')->searchable()->copyable(),
            TextColumn::make('provider')->badge(),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'paid', 'settlement' => 'success',
                'pending' => 'warning',
                default => 'danger',
            }),
            TextColumn::make('payment_channel')->label('Channel')->placeholder('-'),
            TextColumn::make('gross_amount')->label('Amount')->numeric()->prefix('Rp '),
            TextColumn::make('last_checked_at')->dateTime()->placeholder('Belum dicek'),
            TextColumn::make('expires_at')->dateTime(),
        ])->recordActions([
            Action::make('check_status')
                ->label('Cek status Midtrans')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (PaymentTransaction $record): bool => $record->provider === 'midtrans' && $record->status === 'pending' && auth()->user()?->can('check_payment'))
                ->action(fn (PaymentTransaction $record): PaymentTransaction => app(MidtransPaymentService::class)->checkStatus($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPaymentTransactions::route('/')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_payment_transactions') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_payment_transaction') ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
