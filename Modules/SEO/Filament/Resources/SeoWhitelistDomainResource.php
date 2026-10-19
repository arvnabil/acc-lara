<?php

namespace Modules\SEO\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Pages;
use Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Schemas\SeoWhitelistDomainForm;
use Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Tables\SeoWhitelistDomainTable;
use Modules\SEO\Models\SeoWhitelistDomain;

class SeoWhitelistDomainResource extends Resource
{
    protected static ?string $model = SeoWhitelistDomain::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static string | \UnitEnum | null $navigationGroup = 'Seo Management';
    protected static ?string $navigationLabel = 'Whitelist Domains';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema { return $schema->components(SeoWhitelistDomainForm::schema()); }
    public static function table(Table $table): Table { return SeoWhitelistDomainTable::table($table); }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoWhitelistDomains::route('/'),
            'create' => Pages\CreateSeoWhitelistDomain::route('/create'),
            'edit' => Pages\EditSeoWhitelistDomain::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool { return auth()->user()?->can('view_seo_whitelist') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_seo_whitelist') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('update_seo_whitelist') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_seo_whitelist') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('update_seo_whitelist') ?? false; }
    public static function canDeleteAny(): bool { return auth()->user()?->can('update_seo_whitelist') ?? false; }
}
