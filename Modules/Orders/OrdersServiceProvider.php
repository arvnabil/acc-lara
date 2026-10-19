<?php

namespace Modules\Orders;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Orders\Jobs\AutoCompleteShippedOrdersJob;
use Modules\Orders\Jobs\ExpirePendingOrdersJob;
use Modules\Payments\Jobs\CheckPendingMidtransPaymentsJob;

class OrdersServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Orders';

    public function boot(): void
    {
        parent::boot();
        app(Schedule::class)->job(new AutoCompleteShippedOrdersJob)->daily()->withoutOverlapping()->onOneServer();
        app(Schedule::class)->job(new CheckPendingMidtransPaymentsJob)->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        app(Schedule::class)->job(new ExpirePendingOrdersJob)->hourly()->withoutOverlapping()->onOneServer();
    }
}
