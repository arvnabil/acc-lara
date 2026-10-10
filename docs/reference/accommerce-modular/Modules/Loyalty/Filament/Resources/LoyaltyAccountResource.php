<?php

namespace Modules\Loyalty\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Pages\ListLoyaltyAccounts;
use Modules\Loyalty\Models\LoyaltyAccount;
use UnitEnum;

/** Read-only: mutasi poin hanya lewat LoyaltyService, bukan edit manual. */
class LoyaltyAccountResource extends Resource
{
    protected static ?string $model = LoyaltyAccount::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';
    protected static string|UnitEnum|null $navigationGroup = 'Loyalty';

    public static function canViewAny(): bool { return Gate::allows('view_any_loyalty'); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Schema $schema): Schema { return $schema; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('company_id')->label('Perusahaan'),
            TextColumn::make('current_tier')->badge(),
            TextColumn::make('tier_points')->sortable(),
            TextColumn::make('points_balance')->sortable(),
            TextColumn::make('tier_valid_until')->date(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLoyaltyAccounts::route('/')];
    }
}
