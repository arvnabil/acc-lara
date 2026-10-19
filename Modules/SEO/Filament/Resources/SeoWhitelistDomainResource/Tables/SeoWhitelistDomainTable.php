<?php

namespace Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeoWhitelistDomainTable
{
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('domain')->searchable()->sortable()->copyable(),
            TextColumn::make('description')->limit(50),
            IconColumn::make('is_active')->boolean()->label('Active'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([EditAction::make(), DeleteAction::make()])->bulkActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }
}
