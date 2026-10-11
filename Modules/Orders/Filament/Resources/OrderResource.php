<?php

namespace Modules\Orders\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Orders\Filament\Resources\OrderResource\Pages;
use Modules\Orders\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string | \UnitEnum | null $navigationGroup = 'Orders';
    public static function form(Schema $form): Schema { return $form->schema([]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('number')->searchable()->copyable(),
        Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
        Tables\Columns\TextColumn::make('status')->badge(),
        Tables\Columns\TextColumn::make('grand_total')->numeric(),
        Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
    ])->defaultSort('created_at', 'desc'); }
    public static function getPages(): array { return ['index' => Pages\ListOrders::route('/')]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_order') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_order') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
