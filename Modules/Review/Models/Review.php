<?php

namespace Modules\Review\Models;

use App\Models\User;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Models\Company;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;

class Review extends Model
{
    use HasFactory;

    protected static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }

    protected $fillable = ['order_id', 'order_item_id', 'product_id', 'user_id', 'company_id', 'rating', 'body', 'is_approved', 'is_anonymous', 'media'];

    protected $casts = ['is_approved' => 'boolean', 'is_anonymous' => 'boolean', 'media' => 'array'];

    protected static function booted(): void
    {
        static::deleting(function (Review $review): void {
            foreach ($review->media ?? [] as $media) {
                if (isset($media['path'])) {
                    Storage::disk('public')->delete($media['path']);
                }
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
