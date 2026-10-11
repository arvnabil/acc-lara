<?php

namespace Modules\Orders\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderStateMachine;

class ExpirePendingOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(OrderStateMachine $machine): void
    {
        Order::query()->where('status', OrderStatus::PendingPayment)->where('created_at', '<', now()->subHours(config('orders.pending_ttl_hours', 24)))->each(fn (Order $order) => $machine->transition($order, OrderStatus::Cancelled));
    }
}
