<?php

namespace Modules\Payments\Services;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;

class ManualPaymentService
{
    public function submit(Order $order, int $actorId): Order
    {
        abort_unless(in_array($order->payment_method, ['qris', 'bank_transfer'], true), 422, 'Metode pembayaran manual tidak dikenal.');
        abort_unless($order->status === OrderStatus::PendingPayment, 422, 'Order tidak menunggu pembayaran.');

        return DB::transaction(function () use ($order, $actorId): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === OrderStatus::PendingPayment, 422, 'Order sudah diproses.');
            $locked->update(['status' => OrderStatus::AwaitingVerification]);
            $locked->statusHistory()->create(['from_status' => OrderStatus::PendingPayment->value, 'to_status' => OrderStatus::AwaitingVerification->value, 'actor_id' => $actorId, 'notes' => 'manual_payment_submitted', 'created_at' => now()]);

            return $locked->fresh();
        });
    }
}
