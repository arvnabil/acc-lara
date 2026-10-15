<?php

namespace Modules\Catalog\Filament\Resources\BrandResource\Tables;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\BrandResource\Pages;
use Modules\Catalog\Models\Brand;

class BrandTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\ImageColumn::make('logo_url')
                        ->label('Logo')
                        ->size(40)
                        ->circular(),
    
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
    
                    Tables\Columns\TextColumn::make('slug')
                        ->searchable()
                        ->toggleable(),
    
                    Tables\Columns\TextColumn::make('products_count')
                        ->counts('products')
                        ->label('Products')
                        ->sortable(),
    
                    Tables\Columns\TextColumn::make('deleted_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
    
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TrashedFilter::make(),
                ])
                ->actions([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                    \Filament\Actions\RestoreAction::make(),
                ])
                ->bulkActions([
                    \Filament\Actions\BulkActionGroup::make([
                        \Filament\Actions\DeleteBulkAction::make(),
                        \Filament\Actions\RestoreBulkAction::make(),
                        \Filament\Actions\ForceDeleteBulkAction::make(),
                    ]),
                ])
                ->defaultSort('name');
        }
}


