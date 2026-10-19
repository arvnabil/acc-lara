<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Tables;

use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\Filament\Resources\ProductResource\Pages;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\StockMovement;

class ProductTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\ImageColumn::make('images.url')
                        ->label('')
                        ->disk('public')
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

                    \Filament\Actions\Action::make('check_shipping_data')
                        ->label('Cek data ongkir')
                        ->tooltip('Cek kelengkapan data ongkir')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->iconButton()
                        ->color('warning')
                        ->action(function (Product $record): void {
                            $issues = self::shippingDataIssues($record);

                            if ($issues === []) {
                                Notification::make()
                                    ->title('Data ongkir lengkap')
                                    ->body('Berat dan seluruh dimensi produk sudah siap dikirim ke Biteship.')
                                    ->success()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Data ongkir belum lengkap')
                                ->body(implode("\n", $issues))
                                ->warning()
                                ->persistent()
                                ->send();
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

    /**
     * @return array<int, string>
     */
    private static function shippingDataIssues(Product $product): array
    {
        $product->loadMissing('variants');

        $fields = [
            'weight_grams' => 'berat',
            'length_cm' => 'panjang',
            'width_cm' => 'lebar',
            'height_cm' => 'tinggi',
        ];

        /** @var Collection<int, Product|object> $records */
        $records = $product->type === 'variable'
            ? $product->variants
            : collect([$product]);

        $issues = [];

        foreach ($records as $record) {
            $missing = [];

            foreach ($fields as $field => $label) {
                if (! is_numeric($record->{$field}) || (int) $record->{$field} < 1) {
                    $missing[] = $label;
                }
            }

            if ($missing !== []) {
                $recordLabel = $record instanceof Product
                    ? 'Produk utama'
                    : 'Varian '.($record->name ?: $record->sku ?: '#'.$record->id);

                $issues[] = $recordLabel.': '.implode(', ', $missing).'.';
            }
        }

        if ($records->isEmpty()) {
            $issues[] = 'Belum ada varian yang memiliki data ongkir.';
        }

        return $issues;
    }
}


