<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;

class AwardEligiblePointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(): void
    {
        Order::query()
            ->where('status', OrderStatus::Completed)
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('payment_type', 'direct')->where('completed_at', '<=', now()->subDays((int) config('loyalty.redemption.return_days', 7)));
                })->orWhere(function ($query): void {
                    $query->where('payment_type', 'net')->where('invoice_paid_at', '<=', now()->subDays((int) config('loyalty.redemption.return_days', 7)));
                });
            })
            ->whereNull('points_awarded_at')
            ->whereNull('points_skipped_reason')
            ->each(fn (Order $order) => ProcessOrderPointsJob::dispatchSync($order->id));
    }
}
