<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_order_id',
        'attempt',
        'snap_token',
        'redirect_url',
        'status',
        'payment_channel',
        'gross_amount',
        'expires_at',
        'paid_at',
        'last_checked_at',
        'raw',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'gross_amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'raw' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
