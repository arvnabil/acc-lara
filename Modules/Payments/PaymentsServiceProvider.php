<?php

namespace Modules\Payments;

use App\Support\Modules\BaseModuleServiceProvider;

class PaymentsServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Payments';

    protected function bootModule(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'payments');
    }
}
