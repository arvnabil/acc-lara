<?php

namespace Modules\Loyalty\Console;

use Illuminate\Console\Command;
use Modules\Accounts\Models\Company;
use Modules\Loyalty\Services\LoyaltyService;

class BackfillLoyaltyAccounts extends Command
{
    protected $signature = 'loyalty:backfill';
    protected $description = 'Create missing loyalty accounts for companies';

    public function handle(LoyaltyService $loyalty): int
    {
        Company::query()->each(fn (Company $company) => $loyalty->createAccount($company->id));
        $this->info('Loyalty accounts backfilled.');

        return self::SUCCESS;
    }
}
