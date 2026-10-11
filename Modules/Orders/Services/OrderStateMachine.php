<?php

namespace Modules\Orders\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\StockMovement;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatusHistory;
use Modules\Promo\Models\PromoCode;

class OrderStateMachine
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'pending_payment' => ['awaiting_verification', 'cancelled'],
        'awaiting_verification' => ['paid', 'cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'returned'],
        'completed' => ['returned'],
    ];

    /** @param array<string, mixed> $attributes */
    public function transition(Order $order, OrderStatus $to, ?int $actorId = null, array $attributes = []): Order
    {
        return DB::transaction(function () use ($order, $to, $actorId, $attributes): Order {
            $from = $order->status->value;
            abort_unless(in_array($to->value, self::TRANSITIONS[$from] ?? [], true), 422, "Transisi {$from} ke {$to->value} tidak valid.");

            $order->update([
                ...$attributes,
                'status' => $to,
                'shipped_at' => $to === OrderStatus::Shipped ? now() : $order->shipped_at,
                'completed_at' => $to === OrderStatus::Completed ? now() : $order->completed_at,
            ]);
            $order->statusHistory()->create(['from_status' => $from, 'to_status' => $to->value, 'actor_id' => $actorId, 'created_at' => now()]);

            if (in_array($to, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
                $this->restoreStock($order, $to === OrderStatus::Cancelled ? 'cancel' : 'returned');
                if ($order->promo_code_id) {
                    PromoCode::whereKey($order->promo_code_id)->lockForUpdate()->where('used_count', '>', 0)->decrement('used_count');
                }
                if ($order->voucher_id) {
                    $order->voucher()->lockForUpdate()->first()?->update(['used_at' => null]);
                }
            }

            if ($to === OrderStatus::Returned) {
                Event::dispatch(new \App\Support\Events\OrderReturned($order));
            }

            return $order->fresh();
        });
    }

    private function restoreStock(Order $order, string $reason): void
    {
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $model = $item->variant_id ? $item->variant()->lockForUpdate()->first() : $item->product()->lockForUpdate()->first();
            $model?->increment('stock', $item->quantity);
            StockMovement::create(['product_id' => $item->product_id, 'variant_id' => $item->variant_id, 'delta' => $item->quantity, 'reason' => $reason, 'reference_id' => $order->id]);
        }
    }
}
