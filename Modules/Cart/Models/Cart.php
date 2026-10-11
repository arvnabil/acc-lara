<?php

namespace Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Promo\Models\PromoCode;
use Modules\Promo\Models\Voucher;
use Database\Factories\CartFactory;

class Cart extends Model
{
    use HasFactory;
    protected static function newFactory(): CartFactory { return CartFactory::new(); }
    protected $fillable = ['user_id', 'session_id', 'promo_code_id', 'voucher_id'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function promoCode(): BelongsTo { return $this->belongsTo(PromoCode::class); }
    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
    public function items(): HasMany { return $this->hasMany(CartItem::class); }
}
