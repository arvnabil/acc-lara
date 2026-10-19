<?php

namespace Modules\Promo\Filament\Resources;

use Modules\Promo\Filament\Resources\PromoCodeResource\Schemas\PromoCodeForm;
use Modules\Promo\Filament\Resources\PromoCodeResource\Tables\PromoCodeTable;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Loyalty';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $form): Schema
    {
        return PromoCodeForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return PromoCodeTable::table($table);
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
