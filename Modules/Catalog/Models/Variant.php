<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'source_id',
        'sku',
        'name',
        'regular_price',
        'sale_price',
        'is_dummy_price',
        'is_price_outlier',
        'stock',
    ];

    protected $casts = [
        'is_dummy_price' => 'boolean',
        'is_price_outlier' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VariantImage::class)->orderBy('sort_order');
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
