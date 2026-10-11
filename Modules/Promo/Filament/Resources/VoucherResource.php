<?php

namespace Modules\Promo\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promo\Filament\Resources\VoucherResource\Pages;
use Modules\Promo\Models\Voucher;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-gift';
    protected static string | \UnitEnum | null $navigationGroup = 'Promo';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $form): Schema { return $form->schema([]); }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['company', 'category']))
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('discount_value')->numeric(),
                Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
                Tables\Columns\TextColumn::make('source')->badge(),
                Tables\Columns\TextColumn::make('category.name')->label('Category'),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('used_at')->dateTime()->placeholder('Unused'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')->options(['tier_bronze' => 'Tier Bronze', 'redeem' => 'Redeem', 'free_shipping' => 'Free Shipping']),
                Tables\Filters\SelectFilter::make('type')->options(['fixed' => 'Fixed', 'percent' => 'Percent', 'free_shipping' => 'Free Shipping']),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array { return ['index' => Pages\ListVouchers::route('/')]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_voucher') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_voucher') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
