<?php

namespace Modules\Content\Filament\Resources;

use Modules\Content\Filament\Resources\BannerResource\Schemas\BannerForm;
use Modules\Content\Filament\Resources\BannerResource\Tables\BannerTable;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\BannerResource\Pages;
use Modules\Content\Models\Banner;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $form): Schema
    {
        return BannerForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return BannerTable::table($table);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBanners::route('/'), 'create' => Pages\CreateBanner::route('/create'), 'edit' => Pages\EditBanner::route('/{record}/edit')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_banner') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_banner') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_banner') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_banner') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_banner') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_banner') ?? false;
    }
}
