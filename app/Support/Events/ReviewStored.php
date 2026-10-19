<?php

namespace App\Support\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class ReviewStored implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $reviewId,
        public int $clientId,
        public int $userId,
        public int $productId,
        public int $orderId,
    ) {}
}
