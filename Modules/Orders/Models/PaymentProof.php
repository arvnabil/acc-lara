<?php

namespace Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\PaymentProofFactory;

class PaymentProof extends Model
{
    use HasFactory;
    protected static function newFactory(): PaymentProofFactory { return PaymentProofFactory::new(); }
    public $timestamps = false;
    protected $fillable = ['order_id', 'file_path', 'uploaded_at', 'verified_at', 'verified_by'];
    protected $casts = ['uploaded_at' => 'datetime', 'verified_at' => 'datetime'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
