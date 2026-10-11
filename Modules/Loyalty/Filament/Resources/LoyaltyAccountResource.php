<?php

namespace Modules\Loyalty\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Loyalty\Filament\Resources\LoyaltyAccountResource\Pages;
use Modules\Loyalty\Models\LoyaltyAccount;

class LoyaltyAccountResource extends Resource
{
    protected static ?string $model = LoyaltyAccount::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-star';
    protected static string | \UnitEnum | null $navigationGroup = 'Loyalty';
    public static function form(Schema $form): Schema { return $form->schema([]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
        Tables\Columns\TextColumn::make('current_tier')->badge(),
        Tables\Columns\TextColumn::make('tier_points')->numeric(),
        Tables\Columns\TextColumn::make('points_balance')->numeric(),
        Tables\Columns\TextColumn::make('tier_valid_until')->date(),
    ])->actions([
        Tables\Actions\Action::make('adjust')->label('Adjust Points')->visible(fn (): bool => auth()->user()?->can('adjust_loyalty') ?? false)->form([
            TextInput::make('points')->numeric()->integer()->required(),
            Textarea::make('reason')->required()->minLength(3),
        ])->action(fn (LoyaltyAccount $record, array $data): mixed => app(LoyaltyService::class)->adjust($record, (int) $data['points'], $data['reason'], auth()->id())),
    ]); }
    public static function getPages(): array { return ['index' => Pages\ListLoyaltyAccounts::route('/')]; }
    public static function getRelations(): array { return [LoyaltyAccountResource\RelationManagers\PointLedgerRelationManager::class]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_loyalty') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_loyalty') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
