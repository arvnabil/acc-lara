<?php

namespace Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Tables;

use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Pages;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Services\LoyaltyService;

class LoyaltyAccountTable
{
    public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
                Tables\Columns\TextColumn::make('current_tier')->badge(),
                Tables\Columns\TextColumn::make('tier_points')->numeric(),
                Tables\Columns\TextColumn::make('points_balance')->numeric(),
                Tables\Columns\TextColumn::make('tier_valid_until')->date(),
            ])->actions([
                Actions\Action::make('adjust')->label('Adjust Points')->visible(fn (): bool => auth()->user()?->can('adjust_loyalty') ?? false)->form([
                    TextInput::make('points')->numeric()->integer()->required(),
                    Textarea::make('reason')->required()->minLength(3),
                ])->action(fn (LoyaltyAccount $record, array $data): mixed => app(LoyaltyService::class)->adjust($record, (int) $data['points'], $data['reason'], auth()->id())),
            ]);
        }
}


