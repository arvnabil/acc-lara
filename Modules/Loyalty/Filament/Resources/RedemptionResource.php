<?php

namespace Modules\Loyalty\Filament\Resources;

use Modules\Loyalty\Filament\Resources\RedemptionResource\Schemas\RedemptionForm;
use Modules\Loyalty\Filament\Resources\RedemptionResource\Tables\RedemptionTable;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\RedemptionResource\Pages;
use Modules\Loyalty\Models\Redemption;
use Modules\Loyalty\Services\LoyaltyService;

class RedemptionResource extends Resource
{
    protected static ?string $model = Redemption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Loyalty';

    public static function form(Schema $form): Schema
    {
        return RedemptionForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return RedemptionTable::table($table);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRedemptions::route('/')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_loyalty') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_loyalty') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
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
