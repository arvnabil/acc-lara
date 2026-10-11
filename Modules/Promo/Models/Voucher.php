<?php

namespace Modules\Promo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounts\Models\Company;
use Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cart\Models\Cart;
use Modules\Orders\Models\Order;
use Modules\Loyalty\Models\Redemption;
use Database\Factories\VoucherFactory;

class Voucher extends Model
{
    use HasFactory;
    protected static function newFactory(): VoucherFactory { return VoucherFactory::new(); }
    protected $fillable = [
        'code', 'title', 'type', 'discount_value', 'min_order', 'company_id', 'category_id',
        'source', 'points_cost', 'used_at', 'expires_at', 'quarter_key', 'month_key', 'slot',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'min_order' => 'integer',
            'points_cost' => 'integer',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
            'slot' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function carts(): HasMany { return $this->hasMany(Cart::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function redemptions(): HasMany { return $this->hasMany(Redemption::class); }
}
