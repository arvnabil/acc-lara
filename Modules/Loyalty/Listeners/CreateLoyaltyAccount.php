<?php

namespace Modules\Loyalty\Listeners;

use Modules\Clients\Events\ClientRegistered;
use Modules\Loyalty\Services\LoyaltyService;

class CreateLoyaltyAccount
{
    public function __construct(private readonly LoyaltyService $loyalty) {}

    public function handle(ClientRegistered $event): void
    {
        $this->loyalty->createAccount($event->client->id);
    }
}




