<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SEO\Traits\HasSeoMeta;

class Brand extends Model
{
    use SoftDeletes, HasSeoMeta;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
