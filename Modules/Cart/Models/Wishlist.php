<?php

namespace Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Database\Factories\WishlistFactory;

class Wishlist extends Model
{
    use HasFactory;
    protected static function newFactory(): WishlistFactory { return WishlistFactory::new(); }
    public $timestamps = false;
    protected $fillable = ['user_id', 'product_id', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
