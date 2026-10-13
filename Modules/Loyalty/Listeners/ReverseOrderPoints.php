<?php

namespace Modules\Loyalty\Listeners;

use Modules\Orders\Events\OrderReturned;
use Modules\Loyalty\Services\LoyaltyService;

class ReverseOrderPoints
{
    public function __construct(private readonly LoyaltyService $loyalty) {}

    public function handle(OrderReturned $event): void
    {
        $this->loyalty->reverseForOrder($event->order);
    }
}
