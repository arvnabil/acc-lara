<?php

namespace Modules\Promo\Filament\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promo\Filament\Resources\PromoCodeResource\Pages;
use Modules\Promo\Models\PromoCode;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';
    protected static string | \UnitEnum | null $navigationGroup = 'Promo';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Promo Code')->schema([
                Forms\Components\TextInput::make('code')
                    ->required()->maxLength(50)->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim($state ?? ''))),
                Forms\Components\TextInput::make('label')->required()->maxLength(255),
                Forms\Components\Select::make('type')->required()->options(['percent' => 'Percent', 'fixed' => 'Fixed'])->live(),
                Forms\Components\TextInput::make('value')->required()->numeric()->minValue(0),
                Forms\Components\TextInput::make('min_order')->label('Minimum Order')->required()->numeric()->minValue(0)->default(0),
                Forms\Components\TextInput::make('max_uses')->label('Quota')->numeric()->minValue(1)->nullable(),
                Forms\Components\TextInput::make('used_count')->label('Used Count')->numeric()->minValue(0)->default(0)->readOnly(),
                Forms\Components\DateTimePicker::make('valid_from')->label('Valid From'),
                Forms\Components\DateTimePicker::make('valid_until')->label('Valid Until')->after('valid_from'),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            ])->columns(2),
        ]);
    }

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_promo_code') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_promo_code') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_promo_code') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_promo_code') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('delete_promo_code') ?? false; }
    public static function canDeleteAny(): bool { return auth()->user()?->can('delete_promo_code') ?? false; }
}
