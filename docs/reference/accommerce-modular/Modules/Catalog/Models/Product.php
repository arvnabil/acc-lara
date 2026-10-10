<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'in_stock' => 'boolean',
            'is_dummy_price' => 'boolean',
            'is_price_outlier' => 'boolean',
            'is_flash_sale' => 'boolean',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }

    /** Katalog publik memakai nama query string berbahasa Indonesia (lihat 03-data-services). */
    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($f['kategori'] ?? null, fn ($q, $v) => $q->whereHas('category',
                fn ($c) => $c->where('full_path', $v)->orWhere('full_path', 'like', "{$v}/%")))
            ->when($f['brand'] ?? null, fn ($q, $v) => $q->whereHas('brand', fn ($b) => $b->where('slug', $v)))
            ->when(($f['stok'] ?? null) === '1', fn ($q) => $q->where('in_stock', true))
            ->when($f['min'] ?? null, fn ($q, $v) => $q->where('price', '>=', (int) $v))
            ->when($f['max'] ?? null, fn ($q, $v) => $q->where('price', '<=', (int) $v));
    }

    public function scopeSortBy(Builder $q, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $q->orderBy('price')->orderBy('id'),
            'price_desc' => $q->orderByDesc('price')->orderBy('id'),
            'name_asc' => $q->orderBy('name')->orderBy('id'),
            default => $q->orderByDesc('is_flash_sale')->orderBy('id'),   // tie-break stabil
        };
    }
}
