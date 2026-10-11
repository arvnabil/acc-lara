<?php

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Database\Factories\CartItemFactory;

class CartItem extends Model
{
    use HasFactory;
    protected static function newFactory(): CartItemFactory { return CartItemFactory::new(); }
    protected $fillable = ['cart_id', 'product_id', 'variant_id', 'quantity', 'rental_start', 'rental_end', 'rental_start_time', 'rental_end_time'];
    protected $casts = ['rental_start' => 'date', 'rental_end' => 'date'];
    public function cart(): BelongsTo { return $this->belongsTo(Cart::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(Variant::class); }
}
