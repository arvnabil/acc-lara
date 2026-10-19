<?php

namespace Modules\Loyalty\Filament\Resources\PointLedgerResource\Tables;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\PointLedgerResource\Pages;
use Modules\Loyalty\Models\PointLedger;
use Modules\Loyalty\Services\LoyaltyService;

class PointLedgerTable
{
    public static function table(Table $table): Table { return $table->columns([
            Tables\Columns\TextColumn::make('loyaltyAccount.client.name')->label('Client')->searchable(),
            Tables\Columns\TextColumn::make('type')->badge(), Tables\Columns\TextColumn::make('points')->numeric(),
            Tables\Columns\TextColumn::make('earned_at')->dateTime()->sortable(), Tables\Columns\TextColumn::make('available_at')->label('Available At')->state(fn (PointLedger $record) => ($record->available_at ?? $record->earned_at?->copy()->addDays((int) config('loyalty.redemption.maturity_days', 7)))?->format('M d, Y H:i:s')), Tables\Columns\TextColumn::make('expires_at')->dateTime(),
        ])->actions([
            Actions\Action::make('release_for_redemption')
                ->label('Release')
                ->icon('heroicon-o-lock-open')
                ->color('warning')
                ->visible(fn (PointLedger $record): bool => (auth()->user()?->can('adjust_loyalty') ?? false)
                    && $record->points > 0
                    && (int) $record->remaining_points > 0
                    && now()->lt($record->available_at ?? $record->earned_at->copy()->addDays((int) config('loyalty.redemption.maturity_days', 7))))
                ->requiresConfirmation()
                ->modalHeading('Release points for redemption')
                ->modalDescription('This bypasses the 7-day waiting period for this ledger entry.')
                ->action(function (PointLedger $record): void {
                    app(LoyaltyService::class)->releaseLedger($record, auth()->id());
                    Notification::make()->title('Points released')->success()->send();
                }),
        ])->defaultSort('earned_at', 'desc'); }
}
