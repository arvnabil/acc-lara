<?php

namespace Modules\Orders\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderStateMachine;
use Modules\Payments\Services\MidtransPaymentService;

class ExpirePendingOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(OrderStateMachine $machine, ?MidtransPaymentService $midtrans = null): void
    {
        Order::query()->where('status', OrderStatus::PendingPayment)->where('created_at', '<', now()->subHours(config('orders.pending_ttl_hours', 24)))->with('paymentTransactions')->each(function (Order $order) use ($machine, $midtrans): void {
            if ($order->payment_method === 'midtrans' && $midtrans) {
                $transaction = $order->paymentTransactions->where('provider', 'midtrans')->sortByDesc('id')->first();
                if ($transaction) {
                    try {
                        $midtrans->checkStatus($transaction);
                        if ($order->fresh()->status !== OrderStatus::PendingPayment) {
                            return;
                        }
                    } catch (\Throwable) {
                        return;
                    }
                }
            }

            $machine->transition($order, OrderStatus::Cancelled);
        });
    }
}
