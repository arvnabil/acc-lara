<?php

namespace Modules\Catalog\Filament\Resources;

use Modules\Catalog\Filament\Resources\ProductResource\Schemas\ProductForm;
use Modules\Catalog\Filament\Resources\ProductResource\Tables\ProductTable;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Filament\Resources\ProductResource\Pages;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\StockMovement;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | \UnitEnum | null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Products';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $form): Schema
    {
        return ProductForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return ProductTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withTrashed()
            ->with([
                'brand:id,name,slug',
                'images:id,product_id,url,sort_order',
            ]);
    }

    public static function imageDirectory(?ProductImage $record = null): string
    {
        $product = $record?->product_id
            ? Product::query()->with('brand')->find($record->product_id)
            : null;

        return self::canonicalImageDirectory($product);
    }

    public static function canonicalImageDirectory(?Product $product): string
    {
        $brand = Str::slug($product?->brand?->slug ?? $product?->brand?->name ?? 'tanpa-brand');
        $slug = Str::slug($product?->slug ?? $product?->name ?? 'produk');

        return "catalog/{$brand}/{$slug}";
    }

    public static function normalizeImagePaths(Product $product): void
    {
        $product->loadMissing('brand');
        $directory = self::canonicalImageDirectory($product);
        $disk = Storage::disk('public');

        foreach ($product->images()->get() as $image) {
            $path = ltrim((string) $image->url, '/');
            if (blank($path) || Str::startsWith($path, ['http://', 'https://'])) {
                continue;
            }

            $target = $directory.'/'.basename($path);
            if ($path !== $target && $disk->exists($path)) {
                if (! $disk->exists($target)) {
                    $disk->makeDirectory($directory);
                    $disk->move($path, $target);
                }

                $image->update(['url' => $target]);
            }
        }
    }
}
