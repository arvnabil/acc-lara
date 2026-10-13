<?php

namespace Modules\Loyalty;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Accounts\Events\CompanyRegistered;
use Modules\Loyalty\Console\BackfillLoyaltyAccounts;
use Modules\Loyalty\Jobs\ExpirePointsJob;
use Modules\Loyalty\Jobs\AwardEligiblePointsJob;
use Modules\Loyalty\Jobs\EvaluateYearEndTierJob;
use Modules\Loyalty\Jobs\IssueTierBenefitsJob;
use Modules\Loyalty\Jobs\ResetTierPointsJob;
use Modules\Loyalty\Listeners\CreateLoyaltyAccount;
use Modules\Loyalty\Listeners\ReverseOrderPoints;
use Modules\Orders\Events\OrderReturned;

class LoyaltyServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Loyalty';

    public function register(): void
    {
        $this->commands([BackfillLoyaltyAccounts::class]);
    }

    protected function bootModule(): void
    {
        Event::listen(CompanyRegistered::class, CreateLoyaltyAccount::class);
        Event::listen(OrderReturned::class, ReverseOrderPoints::class);
        app(Schedule::class)->job(new ExpirePointsJob)->daily();
        app(Schedule::class)->job(new AwardEligiblePointsJob)->daily();
        app(Schedule::class)->job(new IssueTierBenefitsJob)->monthlyOn(1, '00:05');
        app(Schedule::class)->job(new EvaluateYearEndTierJob)->cron('59 23 31 12 *');
        app(Schedule::class)->job(new ResetTierPointsJob)->cron('0 0 1 1 *');
    }
}
