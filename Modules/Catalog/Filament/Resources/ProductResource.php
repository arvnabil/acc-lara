<?php

namespace Modules\Catalog\Filament\Resources;

use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Catalog\Filament\Resources\ProductResource\Pages;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
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
        return $form->schema([
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
                ])->columns(3),

                // ── Description ────────────────────────────────────────────
                Section::make('Description')->schema([
                    Forms\Components\Textarea::make('short_description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('description')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'bulletList', 'orderedList', 'link',
                            'h2', 'h3', 'blockquote',
                        ]),
                ]),

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

                            Forms\Components\Toggle::make('is_dummy_price')
                                ->label('Dummy Price'),

                            Forms\Components\Toggle::make('is_price_outlier')
                                ->label('Outlier'),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ]),

                // ── Images ─────────────────────────────────────────────────
                Section::make('Images')->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('url')
                                ->url()
                                ->required()
                                ->maxLength(500)
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(4)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            $state['url'] ? Str::limit($state['url'], 60) : null
                        ),
                ]),
            ])->columnSpan(['lg' => 2]),

            // ── Sidebar ───────────────────────────────────────────────────
            Group::make()->schema([
                Section::make('Categories')->schema([
                    Forms\Components\CheckboxList::make('categories')
                        ->relationship('categories', 'name')
                        ->searchable()
                        ->bulkToggleable(),
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
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images.url')
                    ->label('')
                    ->size(48)
                    ->stacked()
                    ->limit(1),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'variable' => 'warning',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('product_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'rental'  => 'info',
                        'license' => 'success',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('regular_price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->options(['simple' => 'Simple', 'variable' => 'Variable']),

                Tables\Filters\SelectFilter::make('product_type')
                    ->label('Product Type')
                    ->options([
                        'regular' => 'Regular',
                        'rental'  => 'Rental',
                        'license' => 'License',
                    ]),

                Tables\Filters\Filter::make('is_featured')
                    ->label('Featured only')
                    ->query(fn ($query) => $query->where('is_featured', true)),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of stock')
                    ->query(fn ($query) => $query->where('stock', '<=', 0)),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\RestoreAction::make(),

                \Filament\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('delta')
                            ->label('Delta (+ add / - remove)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->rows(2),
                    ])
                    ->action(function (Product $record, array $data): void {
                        StockMovement::create([
                            'product_id' => $record->id,
                            'variant_id' => null,
                            'delta'      => (int) $data['delta'],
                            'reason'     => 'adjustment',
                            'note'       => $data['note'] ?? null,
                            'created_at' => now(),
                        ]);

                        $record->increment('stock', (int) $data['delta']);
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
        return parent::getEloquentQuery()->withTrashed();
    }
}
