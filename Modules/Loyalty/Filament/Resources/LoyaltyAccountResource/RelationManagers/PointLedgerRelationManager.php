<?php

namespace Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PointLedgerRelationManager extends RelationManager
{
    protected static string $relationship = 'pointLedger';

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('points')->numeric(),
            Tables\Columns\TextColumn::make('remaining_points')->numeric(),
            Tables\Columns\TextColumn::make('earned_at')->dateTime(),
            Tables\Columns\TextColumn::make('expires_at')->dateTime(),
        ])->headerActions([])->actions([])->bulkActions([]);
    }
}
