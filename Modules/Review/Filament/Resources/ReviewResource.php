<?php

namespace Modules\Review\Filament\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Review\Filament\Resources\ReviewResource\Pages;
use Modules\Review\Models\Review;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Toggle::make('is_approved')->label('Approved'),
            Forms\Components\Textarea::make('media')
                ->label('Media')
                ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                ->disabled()
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('product.name')->searchable(), Tables\Columns\TextColumn::make('company.name')->label('Company'),
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

    public static function getPages(): array
    {
        return ['index' => Pages\ListReviews::route('/'), 'edit' => Pages\EditReview::route('/{record}/edit')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_review') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_review') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_review') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
