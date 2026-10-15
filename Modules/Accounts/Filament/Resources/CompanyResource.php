<?php

namespace Modules\Accounts\Filament\Resources;

use Modules\Accounts\Filament\Resources\CompanyResource\Schemas\CompanyForm;
use Modules\Accounts\Filament\Resources\CompanyResource\Tables\CompanyTable;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounts\Filament\Resources\CompanyResource\Pages;
use Modules\Accounts\Models\Company;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $form): Schema
    {
        return CompanyForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return CompanyTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_company') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_company') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_company') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_company') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('delete_company') ?? false; }
    public static function canDeleteAny(): bool { return auth()->user()?->can('delete_company') ?? false; }
}
