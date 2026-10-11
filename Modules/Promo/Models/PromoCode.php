<?php

namespace Modules\Promo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\PromoCodeFactory;
use Modules\Cart\Models\Cart;
use Modules\Orders\Models\Order;

class PromoCode extends Model
{
    use HasFactory;
    protected static function newFactory(): PromoCodeFactory { return PromoCodeFactory::new(); }
    protected $fillable = [
        'code', 'label', 'type', 'value', 'min_order', 'max_uses', 'used_count',
        'valid_from', 'valid_until', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_order' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function carts(): HasMany { return $this->hasMany(Cart::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }

}
