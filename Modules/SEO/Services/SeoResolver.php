<?php

namespace Modules\SEO\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\SEO\Services\Seo\SeoService;

class SeoResolver
{
    public function __construct(protected SeoService $seoService) {}

    public function for(Model $model): array
    {
        return $this->seoService->for($model);
    }

    public function staticPage(string $title, string $description, string $url, bool $noindex = false): array
    {
        return $this->seoService->staticPage($title, $description, $url, $noindex);
    }
}
