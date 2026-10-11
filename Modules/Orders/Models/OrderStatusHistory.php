<?php

namespace Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\OrderStatusHistoryFactory;

class OrderStatusHistory extends Model
{
    use HasFactory;
    protected $table = 'order_status_history';
    protected static function newFactory(): OrderStatusHistoryFactory { return OrderStatusHistoryFactory::new(); }
    public $timestamps = false;
    protected $fillable = ['order_id', 'from_status', 'to_status', 'actor_id', 'notes', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
