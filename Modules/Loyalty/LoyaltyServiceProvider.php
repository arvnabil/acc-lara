<?php

namespace Modules\Loyalty;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Loyalty\Jobs\AwardEligiblePointsJob;
use Modules\Loyalty\Jobs\EvaluateYearEndTierJob;
use Modules\Loyalty\Jobs\ExpirePointsJob;
use Modules\Loyalty\Jobs\ResetTierPointsJob;
use Modules\Loyalty\Services\LoyaltyService;

class LoyaltyServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Loyalty';

    public function register(): void
    {
        $this->app->singleton(LoyaltyService::class);
    }

    protected function bootModule(): void
    {
        // Modul mendaftarkan jadwalnya sendiri, tidak menyentuh routes/console.php global.
        $this->callAfterResolving(Schedule::class, function (Schedule $s) {
            $s->job(new ExpirePointsJob)->daily();
            $s->job(new AwardEligiblePointsJob)->dailyAt('01:00');
            // Evaluasi 31 Des 23:59 harus SEBELUM reset 1 Jan 00:00.
            $s->job(new EvaluateYearEndTierJob)->cron('59 23 31 12 *');
            $s->job(new ResetTierPointsJob)->cron('0 0 1 1 *');
        });
    }
}
