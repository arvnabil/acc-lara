<?php

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Orders\Models\Order;
use Database\Factories\PointLedgerFactory;

class PointLedger extends Model
{
    use HasFactory;
    protected $table = 'point_ledger';
    protected static function newFactory(): PointLedgerFactory { return PointLedgerFactory::new(); }
    public $timestamps = false;
    protected $fillable = ['loyalty_account_id', 'order_id', 'review_id', 'type', 'points', 'remaining_points', 'counts_for_tier', 'earned_at', 'expires_at', 'idempotency_key', 'notes', 'created_at'];
    protected $casts = ['counts_for_tier' => 'boolean', 'earned_at' => 'datetime', 'expires_at' => 'datetime', 'created_at' => 'datetime'];
    public function loyaltyAccount(): BelongsTo { return $this->belongsTo(LoyaltyAccount::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
