<?php

namespace Modules\SEO;

use App\Support\Modules\BaseModuleServiceProvider;
use Modules\SEO\Models\SeoMeta;
use Modules\SEO\Policies\SeoMetaPolicy;
use Illuminate\Support\Facades\Gate;
use Modules\SEO\Services\Seo\SeoManager;
use Modules\SEO\Services\Seo\SeoService;
use Modules\SEO\Services\SeoResolver;

class SEOServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'SEO';

    protected function bootModule(): void
    {
        Gate::policy(SeoMeta::class, SeoMetaPolicy::class);
    }

    public function register(): void
    {
        parent::register();
        $this->app->singleton(SeoManager::class);
        $this->app->singleton(SeoService::class);
        $this->app->singleton(SeoResolver::class);
    }
}
