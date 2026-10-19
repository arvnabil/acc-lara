<?php

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = [
        'ga4_id', 'gtm_id', 'search_console_verification', 'ga4_property_id', 'ga4_service_account_json', 'default_title',
        'default_description', 'favicon', 'default_og_image',
    ];

    protected function casts(): array
    {
        return ['ga4_service_account_json' => 'encrypted'];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
