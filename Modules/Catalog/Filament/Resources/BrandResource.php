<?php

namespace Modules\Catalog\Filament\Resources;

use Modules\Catalog\Filament\Resources\BrandResource\Schemas\BrandForm;
use Modules\Catalog\Filament\Resources\BrandResource\Tables\BrandTable;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\BrandResource\Pages;
use Modules\Catalog\Models\Brand;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Brand';

    protected static ?string $pluralModelLabel = 'Brands';

    public static function form(Schema $form): Schema
    {
        return BrandForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return BrandTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit'   => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
