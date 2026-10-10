<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Orders\Models\Order;

class AwardEligiblePointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(LoyaltyService $svc): void
    {
        Order::pointsEligible()->chunkById(100, fn ($orders) => $orders->each(fn ($o) => $svc->awardForOrder($o)));
    }
}
