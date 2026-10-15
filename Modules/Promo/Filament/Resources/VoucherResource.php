<?php

namespace Modules\Promo\Filament\Resources;

use Modules\Promo\Filament\Resources\VoucherResource\Schemas\VoucherForm;
use Modules\Promo\Filament\Resources\VoucherResource\Tables\VoucherTable;
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

    public static function form(Schema $form): Schema
    {
        return VoucherForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return VoucherTable::table($table);
    }

    public static function getPages(): array { return ['index' => Pages\ListVouchers::route('/')]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_voucher') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_voucher') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
