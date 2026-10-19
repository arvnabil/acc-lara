<?php

namespace Modules\Promo\Filament\Resources\VoucherResource\Tables;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promo\Filament\Resources\VoucherResource\Pages;
use Modules\Promo\Models\Voucher;

class VoucherTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->modifyQueryUsing(fn ($query) => $query->with(['client', 'category']))
                ->columns([
                    Tables\Columns\TextColumn::make('code')->searchable()->copyable(),
                    Tables\Columns\TextColumn::make('title')->searchable(),
                    Tables\Columns\TextColumn::make('type')->badge(),
                    Tables\Columns\TextColumn::make('discount_value')->numeric(),
                    Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                    Tables\Columns\TextColumn::make('source')->badge(),
                    Tables\Columns\TextColumn::make('category.name')->label('Category'),
                    Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
                    Tables\Columns\TextColumn::make('used_at')->dateTime()->placeholder('Unused'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('source')->options(['tier_bronze' => 'Tier Bronze', 'redeem' => 'Redeem', 'free_shipping' => 'Free Shipping']),
                    Tables\Filters\SelectFilter::make('type')->options(['fixed' => 'Fixed', 'percent' => 'Percent', 'free_shipping' => 'Free Shipping']),
                ])
                ->defaultSort('created_at', 'desc');
        }
}




