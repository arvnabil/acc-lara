<?php

namespace Modules\Catalog;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Console\MigrateProductImages;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Policies\BrandPolicy;
use Modules\Catalog\Policies\CategoryPolicy;
use Modules\Catalog\Policies\ProductPolicy;

class CatalogServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Catalog';

    public function register(): void
    {
        $this->commands([MigrateProductImages::class]);
    }

    protected function bootModule(): void
    {
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
