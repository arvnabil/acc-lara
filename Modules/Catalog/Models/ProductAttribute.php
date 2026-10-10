<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductAttribute extends Model
{
    public $timestamps = false; // Based on migration, product_attributes has no timestamps

    protected $fillable = [
        'name',
        'value',
        'attributable_id',
        'attributable_type',
    ];

    public function attributable(): MorphTo
    {
        return $this->morphTo();
    }
}
