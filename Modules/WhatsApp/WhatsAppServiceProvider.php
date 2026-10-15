<?php

namespace Modules\WhatsApp;

use App\Support\Modules\BaseModuleServiceProvider;

class WhatsAppServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'WhatsApp';

    protected function bootModule(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'whatsapp');
    }
}
