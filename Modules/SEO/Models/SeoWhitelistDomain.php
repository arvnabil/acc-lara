<?php

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Model;

class SeoWhitelistDomain extends Model
{
    protected $fillable = ['domain', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
