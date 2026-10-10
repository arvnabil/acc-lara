<?php

namespace Modules\Catalog\Filament\Resources;

use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Filament\Resources\ProductResource\Pages;
use Modules\Catalog\Models\Product;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static string|UnitEnum|null $navigationGroup = 'Product Catalog';

    // Menu hilang dari sidebar jika user tidak punya permission modul ini.
    public static function canViewAny(): bool { return Gate::allows('view_any_product'); }
    public static function canCreate(): bool { return Gate::allows('create_product'); }
    public static function canEdit($record): bool { return Gate::allows('update_product'); }
    public static function canDelete($record): bool { return Gate::allows('delete_product'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('category_id')->relationship('category', 'name')->searchable(),
            Select::make('brand_id')->relationship('brand', 'name')->searchable(),
            TextInput::make('price')->numeric()->prefix('Rp')->required(),
            TextInput::make('sale_price')->numeric()->prefix('Rp'),
            Toggle::make('in_stock')->default(true),
            Toggle::make('is_dummy_price')->helperText('Harga estimasi, bukan harga resmi.'),
            Toggle::make('is_flash_sale'),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->limit(50),
                TextColumn::make('brand.name')->sortable(),
                TextColumn::make('price')->money('IDR', locale: 'id')->sortable(),
                IconColumn::make('in_stock')->boolean(),
                IconColumn::make('is_dummy_price')->boolean()->label('Harga dummy'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
