<?php

namespace Modules\Loyalty\Filament\Resources;

use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Schemas\LoyaltyAccountForm;
use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Tables\LoyaltyAccountTable;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Pages;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Services\LoyaltyService;

class LoyaltyAccountResource extends Resource
{
    protected static ?string $model = LoyaltyAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|\UnitEnum|null $navigationGroup = 'Loyalty';

    public static function form(Schema $form): Schema
    {
        return LoyaltyAccountForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return LoyaltyAccountTable::table($table);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListLoyaltyAccounts::route('/')];
    }

    public static function getRelations(): array
    {
        return [LoyaltyAccountResource\RelationManagers\PointLedgerRelationManager::class];
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
