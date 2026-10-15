<?php

namespace Modules\Loyalty\Filament\Resources\PointLedgerResource\Tables;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\PointLedgerResource\Pages;
use Modules\Loyalty\Models\PointLedger;

class PointLedgerTable
{
    public static function table(Table $table): Table { return $table->columns([
            Tables\Columns\TextColumn::make('loyaltyAccount.company.name')->label('Company')->searchable(),
            Tables\Columns\TextColumn::make('type')->badge(), Tables\Columns\TextColumn::make('points')->numeric(),
            Tables\Columns\TextColumn::make('earned_at')->dateTime()->sortable(), Tables\Columns\TextColumn::make('expires_at')->dateTime(),
        ])->defaultSort('earned_at', 'desc'); }
}


