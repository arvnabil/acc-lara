<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clients\Models\Client;
use Modules\Orders\Models\Order;

class InvoiceRequest extends Model
{
    protected $fillable = ['order_id', 'client_id', 'tax_profile_id', 'status', 'requested_at', 'processed_at', 'notes', 'invoice_path'];

    protected $casts = ['requested_at' => 'datetime', 'processed_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function taxProfile(): BelongsTo { return $this->belongsTo(TaxProfile::class); }
}
