<?php

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'noindex',
        'twitter_card',
        'schema_markup',
        'schema_override',
        'seo_score',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'schema_markup' => 'array',
            'schema_override' => 'array',
            'noindex' => 'boolean',
            'seo_score' => 'integer',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
