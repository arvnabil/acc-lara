<?php

namespace Modules\Catalog\Filament\Resources;

use Modules\Catalog\Filament\Resources\CategoryResource\Schemas\CategoryForm;
use Modules\Catalog\Filament\Resources\CategoryResource\Tables\CategoryTable;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\CategoryResource\Pages;
use Modules\Catalog\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder-open';

    protected static string | \UnitEnum | null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Category';

    protected static ?string $pluralModelLabel = 'Categories';

    public static function form(Schema $form): Schema
    {
        return CategoryForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return CategoryTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
