<?php

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMonitoringRecord extends Model
{
    protected $fillable = [
        'url', 'model', 'model_id', 'is_noindex', 'in_sitemap',
        'canonical_valid', 'seo_score', 'last_modified',
    ];

    protected function casts(): array
    {
        return [
            'is_noindex' => 'boolean',
            'in_sitemap' => 'boolean',
            'canonical_valid' => 'boolean',
            'seo_score' => 'integer',
            'last_modified' => 'datetime',
        ];
    }
}
