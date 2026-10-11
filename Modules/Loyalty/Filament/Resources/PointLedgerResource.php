<?php

namespace Modules\Loyalty\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Filament\Resources\PointLedgerResource\Pages;
use Modules\Loyalty\Models\PointLedger;

class PointLedgerResource extends Resource
{
    protected static ?string $model = PointLedger::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';
    protected static string | \UnitEnum | null $navigationGroup = 'Loyalty';
    public static function form(Schema $form): Schema { return $form->schema([]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('loyaltyAccount.company.name')->label('Company')->searchable(),
        Tables\Columns\TextColumn::make('type')->badge(), Tables\Columns\TextColumn::make('points')->numeric(),
        Tables\Columns\TextColumn::make('earned_at')->dateTime()->sortable(), Tables\Columns\TextColumn::make('expires_at')->dateTime(),
    ])->defaultSort('earned_at', 'desc'); }
    public static function getPages(): array { return ['index' => Pages\ListPointLedgers::route('/')]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_loyalty') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_loyalty') ?? false; }
    public static function canCreate(): bool { return false; } public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; } public static function canDeleteAny(): bool { return false; }
}
