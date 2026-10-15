<?php

namespace Modules\Catalog\Filament\Resources\CategoryResource\Tables;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\CategoryResource\Pages;
use Modules\Catalog\Models\Category;

class CategoryTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
    
                    Tables\Columns\TextColumn::make('parent.name')
                        ->label('Parent')
                        ->searchable()
                        ->sortable()
                        ->placeholder('(root)'),
    
                    Tables\Columns\TextColumn::make('full_path')
                        ->toggleable()
                        ->limit(50),
    
                    Tables\Columns\IconColumn::make('is_addon')
                        ->boolean()
                        ->label('Add-on'),
    
                    Tables\Columns\IconColumn::make('is_brand_group')
                        ->boolean()
                        ->label('Brand Group'),
    
                    Tables\Columns\TextColumn::make('product_count')
                        ->label('Products')
                        ->sortable(),
    
                    Tables\Columns\TextColumn::make('sort_order')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TrashedFilter::make(),
                    Tables\Filters\SelectFilter::make('parent_id')
                        ->label('Parent')
                        ->relationship('parent', 'name'),
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


