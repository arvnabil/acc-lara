<?php

namespace Modules\Loyalty\Filament\Resources;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\RedemptionResource\Pages;
use Modules\Loyalty\Models\Redemption;
use Modules\Loyalty\Services\LoyaltyService;

class RedemptionResource extends Resource
{
    protected static ?string $model = Redemption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Loyalty';

    public static function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('loyaltyAccount.company.name')->label('Company')->searchable(),
            Tables\Columns\TextColumn::make('type')->badge(), Tables\Columns\TextColumn::make('points')->numeric(),
            Tables\Columns\TextColumn::make('amount_idr')->numeric(), Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Actions\Action::make('paid')->label('Mark Paid')->visible(fn (Redemption $record): bool => $record->status === 'pending' && (auth()->user()?->can('process_redemption') ?? false))->action(fn (Redemption $record): mixed => $record->update(['status' => 'paid', 'processed_by' => auth()->id(), 'processed_at' => now()])),
            Actions\Action::make('failed')->label('Mark Failed')->color('danger')->visible(fn (Redemption $record): bool => in_array($record->status, ['pending', 'paid'], true) && (auth()->user()?->can('process_redemption') ?? false))->form([Textarea::make('reason')->required()])->action(fn (Redemption $record, array $data): mixed => app(LoyaltyService::class)->refundRedemption($record, $data['reason'])),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRedemptions::route('/')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_loyalty') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_loyalty') ?? false;
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
