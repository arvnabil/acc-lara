<?php

namespace Modules\Catalog;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Policies\ProductPolicy;

class CatalogServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Catalog';

    protected function bootModule(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
