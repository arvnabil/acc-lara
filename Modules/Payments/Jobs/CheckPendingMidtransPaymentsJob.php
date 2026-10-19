<?php

namespace Modules\Payments\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Models\PaymentTransaction;
use Modules\Payments\Services\MidtransPaymentService;

class CheckPendingMidtransPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(MidtransPaymentService $service): void
    {
        PaymentTransaction::query()->where('provider', 'midtrans')->where('status', 'pending')->where('expires_at', '>', now())->where(function ($query): void {
            $query->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subSeconds(10));
        })->limit(100)->get()->each(function (PaymentTransaction $transaction) use ($service): void {
            try {
                $service->checkStatus($transaction);
            } catch (\Throwable $exception) {
                Log::warning('Midtrans status check failed.', ['payment_transaction_id' => $transaction->id, 'exception' => $exception::class]);
            }
        });
    }
}
