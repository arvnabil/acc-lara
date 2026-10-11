<?php

namespace Modules\Loyalty\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Promo\Models\Voucher;
use Database\Factories\RedemptionFactory;

class Redemption extends Model
{
    use HasFactory;
    protected static function newFactory(): RedemptionFactory { return RedemptionFactory::new(); }
    protected $fillable = ['loyalty_account_id', 'type', 'points', 'amount_idr', 'ewallet_provider', 'ewallet_account', 'ewallet_account_name', 'voucher_id', 'status', 'requested_by', 'processed_by', 'processed_at', 'rejection_reason', 'idempotency_key'];
    protected $casts = ['processed_at' => 'datetime'];
    public function loyaltyAccount(): BelongsTo { return $this->belongsTo(LoyaltyAccount::class); }
    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
