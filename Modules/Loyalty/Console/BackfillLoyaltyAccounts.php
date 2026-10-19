<?php

namespace Modules\Loyalty\Console;

use Illuminate\Console\Command;
use Modules\Clients\Models\Client;
use Modules\Loyalty\Services\LoyaltyService;

class BackfillLoyaltyAccounts extends Command
{
    protected $signature = 'loyalty:backfill';
    protected $description = 'Create missing loyalty accounts for clients';

    public function handle(LoyaltyService $loyalty): int
    {
        Client::query()->each(fn (Client $company) => $loyalty->createAccount($company->id));
        $this->info('Loyalty accounts backfilled.');

        return self::SUCCESS;
    }
}




