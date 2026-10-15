<?php

namespace Modules\Content\Filament\Resources\BannerResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\BannerResource\Pages;
use Modules\Content\Models\Banner;

class BannerTable
{
    public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(), Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(), Tables\Columns\IconColumn::make('is_active')->boolean(), Tables\Columns\TextColumn::make('starts_at')->dateTime(), Tables\Columns\TextColumn::make('expires_at')->dateTime(),
            ])->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
        }
}


