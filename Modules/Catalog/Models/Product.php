<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'type',
        'product_type',
        'regular_price',
        'sale_price',
        'is_dummy_price',
        'is_price_outlier',
        'is_featured',
        'is_flash_sale',
        'is_addon',
        'stock',
        'brand_id',
        'sort_order',
        'description',
        'short_description',
        'source_id',
    ];

    protected $casts = [
        'is_dummy_price' => 'boolean',
        'is_price_outlier' => 'boolean',
        'is_featured' => 'boolean',
        'is_flash_sale' => 'boolean',
        'is_addon' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function attributes(): MorphMany
    {
        return $this->morphMany(ProductAttribute::class, 'attributable');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
