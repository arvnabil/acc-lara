<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Orders\Enums\OrderStatus;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'has_dummy_price_item' => 'boolean',
            'completed_at' => 'datetime',
            'invoice_paid_at' => 'datetime',
            'points_awarded_at' => 'datetime',
        ];
    }

    public function items(): HasMany { return $this->hasMany(OrderItem::class); }

    /** Nilai bersih untuk poin: barang setelah diskon, tanpa PPN/ongkir/admin. */
    public function netValueForPoints(): int
    {
        return max(0, $this->goods_subtotal - $this->discount_on_goods);
    }

    /**
     * Revisi 4: direct = Selesai + 7 hari; NET = invoice Lunas + 7 hari. Belum pernah diberi poin.
     */
    public function scopePointsEligible(Builder $q): Builder
    {
        $cutoff = now()->subDays(7);

        return $q->whereNull('points_awarded_at')
            ->where('status', OrderStatus::Completed)
            ->where(fn ($w) => $w
                ->where(fn ($d) => $d->where('payment_type', 'direct')->where('completed_at', '<=', $cutoff))
                ->orWhere(fn ($n) => $n->where('payment_type', 'net')->where('invoice_paid_at', '<=', $cutoff)));
    }
}
