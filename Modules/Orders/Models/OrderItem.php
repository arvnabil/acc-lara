<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Database\Factories\OrderItemFactory;

class OrderItem extends Model
{
    use HasFactory;
    protected static function newFactory(): OrderItemFactory { return OrderItemFactory::new(); }
    protected $fillable = ['order_id', 'product_id', 'variant_id', 'product_name', 'variant_name', 'sku', 'unit_price', 'quantity', 'subtotal', 'is_dummy_price', 'rental_start', 'rental_end', 'rental_days'];
    protected $casts = ['is_dummy_price' => 'boolean', 'rental_start' => 'date', 'rental_end' => 'date'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(Variant::class); }
    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(\Modules\Review\Models\Review::class); }
}
