<?php

namespace Modules\SEO\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\SEO\Models\SeoMeta;

trait HasSeoMeta
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
