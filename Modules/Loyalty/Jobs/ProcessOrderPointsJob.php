<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Orders\Models\Order;

class ProcessOrderPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public readonly int $orderId) {}

    public function handle(LoyaltyService $loyalty): void
    {
        $order = Order::query()->find($this->orderId);
        if ($order) {
            $loyalty->awardForOrder($order);
        }
    }
}
