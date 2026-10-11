<?php

namespace Modules\Loyalty\Listeners;

use Modules\Accounts\Events\CompanyRegistered;
use Modules\Loyalty\Services\LoyaltyService;

class CreateLoyaltyAccount
{
    public function __construct(private readonly LoyaltyService $loyalty) {}

    public function handle(CompanyRegistered $event): void
    {
        $this->loyalty->createAccount($event->company->id);
    }
}
