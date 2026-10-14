<?php

namespace Modules\Loyalty\Listeners;

use App\Support\Events\ReviewStored;
use Modules\Loyalty\Services\LoyaltyService;

class AwardReviewPoints
{
    public function __construct(private readonly LoyaltyService $loyalty) {}

    public function handle(ReviewStored $event): void
    {
        $this->loyalty->awardForReview($event);
    }
}
