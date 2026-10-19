<?php

namespace Modules\SEO\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\SEO\Filament\Resources\SeoMetaResource\Pages;
use Modules\SEO\Filament\Resources\SeoMetaResource\Schemas\SeoForm;
use Modules\SEO\Filament\Resources\SeoMetaResource\Tables\SeoMetaTable;
use Modules\SEO\Models\SeoMeta;

class SeoMetaResource extends Resource
{
    protected static ?string $model = SeoMeta::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Seo Management';

    protected static ?string $navigationLabel = 'SEO Audit';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(SeoForm::schema());
    }

    public static function table(Table $table): Table
    {
        return SeoMetaTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoMetas::route('/'),
            'edit' => Pages\EditSeoMeta::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_seo_meta') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_seo_meta') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_seo_meta') ?? false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
