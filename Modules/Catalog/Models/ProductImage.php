<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'url',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $image): void {
            if ($image->wasChanged('url')) {
                self::deleteStoredFile($image->getOriginal('url'));
            }
        });

        static::deleted(function (self $image): void {
            self::deleteStoredFile($image->url);
        });
    }

    private static function deleteStoredFile(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
