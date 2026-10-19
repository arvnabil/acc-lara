<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Schemas;

use Modules\Catalog\Filament\Resources\ProductResource;
use Modules\SEO\Filament\Resources\SeoMetaResource\Schemas\SeoForm;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Catalog\Filament\Resources\ProductResource\Pages;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\StockMovement;

class ProductForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Tabs::make('ProductTabs')
                    ->tabs([
                        Tabs\Tab::make('Product Details')
                            ->schema([
                Group::make()->schema([
                    // ── Main Info ──────────────────────────────────────────────
                    Section::make('Product Info')->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state, ?string $old) =>
                                $set('slug', Str::slug($state ?? ''))
                            ),
    
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(500)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
    
                        Forms\Components\TextInput::make('sku')
                            ->maxLength(100),
    
                        Forms\Components\Select::make('type')
                            ->options(['simple' => 'Simple', 'variable' => 'Variable'])
                            ->default('simple')
                            ->live()
                            ->required(),
    
                        Forms\Components\Select::make('product_type')
                            ->options([
                                'regular' => 'Regular',
                                'rental'  => 'Rental',
                                'license' => 'License',
                            ])
                            ->default('regular')
                            ->required(),
    
                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
    
                    // ── Description ────────────────────────────────────────────
                    Section::make('Description')->schema([
                        Forms\Components\RichEditor::make('short_description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList', 'link',
                                'h2', 'h3', 'blockquote',
                            ]),
    
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList', 'link',
                                'h2', 'h3', 'blockquote',
                            ]),
                    ]),

                    Section::make('Shipping Package')->schema([
                        Forms\Components\TextInput::make('weight_grams')
                            ->label('Weight')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->suffix('gram')
                            ->helperText('Use packed product weight.'),

                        Forms\Components\TextInput::make('length_cm')
                            ->label('Length')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->suffix('cm'),

                        Forms\Components\TextInput::make('width_cm')
                            ->label('Width')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->suffix('cm'),

                        Forms\Components\TextInput::make('height_cm')
                            ->label('Height')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->suffix('cm'),
                    ])->columns(4),
    
                    // ── Variants ───────────────────────────────────────────────
                    Section::make('Variants')->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
    
                                Forms\Components\TextInput::make('sku')
                                    ->maxLength(100),
    
                                Forms\Components\TextInput::make('regular_price')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->prefix('Rp'),
    
                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->nullable()
                                    ->prefix('Rp'),
    
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\TextInput::make('weight_grams')
                                    ->label('Weight (g)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('length_cm')
                                    ->label('Length (cm)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('width_cm')
                                    ->label('Width (cm)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('height_cm')
                                    ->label('Height (cm)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1),
    
                                Forms\Components\Toggle::make('is_dummy_price')
                                    ->label('Dummy Price'),
    
                                Forms\Components\Toggle::make('is_price_outlier')
                                    ->label('Outlier'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ])
                        ->visible(fn (Get $get): bool => $get('type') === 'variable'),
    
                    // ── Images ─────────────────────────────────────────────────
                    Section::make('Images')->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship()
                            ->schema([
                                Forms\Components\FileUpload::make('url')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory(fn ($record): string => ProductResource::imageDirectory($record))
                                    ->visibility('public')
                                    ->preserveFilenames()
                                    ->maxSize(10240)
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->grid(4)
                            ->collapsible()
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->itemLabel(fn (array $state): ?string =>
                                $state['url'] ? Str::limit(basename($state['url']), 60) : null
                            ),
                    ]),
                ])->columnSpan(['lg' => 2]),
    
                // ── Sidebar ───────────────────────────────────────────────────
                Group::make()->schema([
                    // ── Pricing & Stock ────────────────────────────────────────
                    Section::make('Pricing & Stock')->schema([
                        Forms\Components\TextInput::make('regular_price')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('Rp'),
    
                        Forms\Components\TextInput::make('sale_price')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp'),
    
                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->required()
                            ->default(0),
    
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
    
                        Forms\Components\Toggle::make('is_dummy_price')
                            ->label('Dummy Price')
                            ->helperText('Harga perlu konfirmasi manual'),
    
                        Forms\Components\Toggle::make('is_price_outlier')
                            ->label('Price Outlier'),
    
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured'),
    
                        Forms\Components\Toggle::make('is_flash_sale')
                            ->label('Flash Sale'),
    
                        Forms\Components\Toggle::make('is_addon')
                            ->label('Is Add-on'),
                    ])->columns(1),
    
                    Section::make('Categories')->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ]),
    
                    Section::make('Meta')->schema([
                        Forms\Components\TextInput::make('source_id')
                            ->label('Source ID')
                            ->maxLength(50)
                            ->disabled(),
    
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created')
                            ->content(fn (?Product $record) => $record?->created_at?->diffForHumans()),
    
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Updated')
                            ->content(fn (?Product $record) => $record?->updated_at?->diffForHumans()),
                    ]),
                ])->columnSpan(['lg' => 1]),
                            ])
                            ->columns(3),
                        Tabs\Tab::make('SEO')
                            ->schema([
                                Group::make()
                                    ->relationship('seo')
                                    ->schema(SeoForm::schema()),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
        }
}


