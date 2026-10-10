<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantImage extends Model
{
    public $timestamps = false; // Based on migration, variant_images has no created_at/updated_at

    protected $fillable = [
        'variant_id',
        'url',
        'sort_order',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
