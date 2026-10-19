<?php

namespace Modules\SEO\Filament\Resources\SeoMetaResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Modules\SEO\Models\SeoMeta;

class SeoMetaTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('seoable'))
            ->columns([
                TextColumn::make('seoable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('seoable_title')
                    ->label('Page Title')
                    ->state(fn (?SeoMeta $record): string => $record?->seoable?->title ?? $record?->seoable?->name ?? 'Untitled'),
                TextColumn::make('title')
                    ->label('Meta Title')
                    ->limit(30)
                    ->description(fn (?SeoMeta $record): string => $record?->title ? 'Set' : 'Missing'),
                IconColumn::make('status_check')
                    ->label('Status')
                    ->state(fn (?SeoMeta $record): bool => filled($record?->title) && filled($record?->description))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('warning'),
                IconColumn::make('noindex')->boolean()->label('No Index'),
                TextColumn::make('seo_score')
                    ->label('Score')
                    ->state(fn (?SeoMeta $record): int => ($record?->title ? 40 : 0) + ($record?->description ? 40 : 0) + ($record?->noindex ? 0 : 20))
                    ->suffix('%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('path')
                    ->label('Route / Path')
                    ->state(fn (?SeoMeta $record): string => $record?->seoable?->slug ? '/produk/'.$record->seoable->slug : 'Missing Page')
                    ->color('gray')
                    ->fontFamily('mono'),
            ])
            ->filters([
                Filter::make('missing_metadata')
                    ->label('Needs Attention')
                    ->query(fn ($query) => $query->where(fn ($query) => $query->whereNull('title')->orWhereNull('description'))),
                Filter::make('noindex')
                    ->label('No Index Pages')
                    ->query(fn ($query) => $query->where('noindex', true)),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('audit')
                    ->label('Audit')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->modalHeading('SEO Audit')
                    ->modalDescription(fn (?SeoMeta $record): string => $record?->seoable?->name ?? $record?->seoable?->title ?? 'SEO metadata')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
