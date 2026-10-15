<?php

namespace Modules\Promo\Filament\Resources\PromoCodeResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promo\Filament\Resources\PromoCodeResource\Pages;
use Modules\Promo\Models\PromoCode;

class PromoCodeTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('code')->searchable()->sortable()->copyable(),
                    Tables\Columns\TextColumn::make('label')->searchable(),
                    Tables\Columns\TextColumn::make('type')->badge(),
                    Tables\Columns\TextColumn::make('value')->numeric(),
                    Tables\Columns\TextColumn::make('used_count')->label('Used'),
                    Tables\Columns\TextColumn::make('max_uses')->label('Quota')->placeholder('Unlimited'),
                    Tables\Columns\TextColumn::make('valid_from')->dateTime()->sortable(),
                    Tables\Columns\TextColumn::make('valid_until')->dateTime()->sortable(),
                    Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('type')->options(['percent' => 'Percent', 'fixed' => 'Fixed']),
                    Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                ])
                ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
                ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])])
                ->defaultSort('created_at', 'desc');
        }
}


