<?php

namespace Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Accounts\Models\Company;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Modules\Orders\Enums\OrderStatus;
use Modules\Promo\Models\PromoCode;
use Modules\Promo\Models\Voucher;
use Database\Factories\OrderFactory;

class Order extends Model
{
    use HasFactory;
    protected static function newFactory(): OrderFactory { return OrderFactory::new(); }
    protected $fillable = ['number', 'user_id', 'company_id', 'status', 'payment_type', 'net_days', 'goods_subtotal', 'discount_on_goods', 'ppn', 'shipping_cost', 'admin_fee', 'grand_total', 'has_dummy_price_item', 'promo_code_id', 'voucher_id', 'shipping_method', 'shipping_label', 'tracking_number', 'shipping_address', 'payment_method', 'paid_at', 'completed_at', 'shipped_at', 'invoice_paid_at', 'points_awarded_at', 'points_skipped_reason', 'customer_notes', 'admin_notes'];
    protected $casts = ['status' => OrderStatus::class, 'shipping_address' => 'array', 'has_dummy_price_item' => 'boolean', 'paid_at' => 'datetime', 'completed_at' => 'datetime', 'shipped_at' => 'datetime', 'invoice_paid_at' => 'datetime', 'points_awarded_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function promoCode(): BelongsTo { return $this->belongsTo(PromoCode::class); }
    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function statusHistory(): HasMany { return $this->hasMany(OrderStatusHistory::class); }
    public function paymentProofs(): HasMany { return $this->hasMany(PaymentProof::class); }
    public function reviews(): HasMany { return $this->hasMany(\Modules\Review\Models\Review::class); }
    public function getPaymentStatusAttribute(): string { return match ($this->status?->value ?? $this->status) { 'pending_payment' => 'unpaid', 'awaiting_verification' => 'awaiting_verification', 'cancelled' => 'cancelled', default => 'paid' }; }
}
