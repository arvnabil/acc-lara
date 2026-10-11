<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Loyalty\Services\LoyaltyService;

class ResetTierPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LoyaltyService $loyalty): void
    {
        $loyalty->resetTierPoints();
    }
}
