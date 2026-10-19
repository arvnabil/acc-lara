<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\VariantFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Cart\Models\CartItem;
use Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory, SoftDeletes;
    protected static function newFactory(): VariantFactory { return VariantFactory::new(); }

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
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
    ];

    protected $casts = [
        'is_dummy_price' => 'boolean',
        'is_price_outlier' => 'boolean',
        'weight_grams' => 'integer',
        'length_cm' => 'integer',
        'width_cm' => 'integer',
        'height_cm' => 'integer',
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

    public function cartItems(): HasMany { return $this->hasMany(CartItem::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}
