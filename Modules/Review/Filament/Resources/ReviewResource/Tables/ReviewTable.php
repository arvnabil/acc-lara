<?php

namespace Modules\Review\Filament\Resources\ReviewResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Review\Filament\Resources\ReviewResource\Pages;
use Modules\Review\Models\Review;

class ReviewTable
{
    public static function table(Table $table): Table
        {
            return $table->columns([
            Tables\Columns\TextColumn::make('product.name')->searchable(), Tables\Columns\TextColumn::make('client.name')->label('Client'),
                Tables\Columns\TextColumn::make('rating')->numeric(), Tables\Columns\TextColumn::make('body')->limit(60),
                Tables\Columns\IconColumn::make('is_approved')->boolean(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])->filters([
                Tables\Filters\SelectFilter::make('rating')->options(array_combine(range(1, 5), range(1, 5))),
                Tables\Filters\TernaryFilter::make('is_approved'),
                Tables\Filters\SelectFilter::make('product_id')->relationship('product', 'name')->searchable(),
            ])->actions([
                Actions\Action::make('approve')->label('Setujui')->color('success')->visible(fn ($record): bool => ! $record->is_approved && auth()->user()?->can('approve_review'))->action(fn ($record) => $record->update(['is_approved' => true])),
                Actions\Action::make('hide')->label('Sembunyikan')->color('warning')->visible(fn ($record): bool => $record->is_approved && auth()->user()?->can('approve_review'))->action(fn ($record) => $record->update(['is_approved' => false])),
                Actions\EditAction::make(),
            ])->defaultSort('created_at', 'desc');
        }
}




