<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Email\Services\EmailNotificationService;
use Modules\Loyalty\Models\PointLedger;
use Modules\Orders\Models\Order;

class ProcessOrderPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public readonly int $orderId) {}

    public function handle(LoyaltyService $loyalty): void
    {
        $order = Order::query()->find($this->orderId);
        if (! $order || $order->points_awarded_at || $order->points_skipped_reason) {
            return;
        }

        $loyalty->awardForOrder($order);
        $order->refresh();

        if ($order->points_awarded_at) {
            $points = (int) PointLedger::query()
                ->where('order_id', $order->id)
                ->sum('points');
            app(EmailNotificationService::class)->pointsAwarded($order, $points);
        }
    }
}
