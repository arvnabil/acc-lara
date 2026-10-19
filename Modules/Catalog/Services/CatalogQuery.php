<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CatalogQuery
{
    public function products(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with([
                'brand:id,name,slug,logo_url',
                'categories:id,name,slug,full_path,parent_id',
                'images:id,product_id,url,sort_order',
                'attributes:id,attributable_type,attributable_id,name,value',
                'seo',
                'variants' => fn ($variantQuery) => $variantQuery
                    ->where('is_price_outlier', false)
                    ->with('images:id,variant_id,url,sort_order'),
            ])
            ->where('is_addon', false)
            ->where('is_price_outlier', false)
            ->whereNull('deleted_at');

        if ($filters['category'] ?? null) {
            $categoryIds = $this->descendantIds((string) $filters['category']);

            $query->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds));
        }

        if ($filters['brand'] ?? null) {
            $query->whereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('slug', $filters['brand']));
        }

        if ($filters['q'] ?? null) {
            $search = trim((string) $filters['q']);
            $query->where(fn (Builder $searchQuery) => $searchQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        if (($filters['stock'] ?? false) === true) {
            $query->where('stock', '>', 0);
        }

        if (($filters['min'] ?? null) !== null) {
            $query->where('regular_price', '>=', (int) $filters['min']);
        }

        if (($filters['max'] ?? null) !== null) {
            $query->where('regular_price', '<=', (int) $filters['max']);
        }

        if (($filters['sort'] ?? null) === 'sale') {
            $query->whereNotNull('sale_price');
        }

        $query = match ($filters['sort'] ?? null) {
            'sale' => $query->orderByDesc('sale_price')->orderBy('sort_order'),
            'price_asc' => $query->orderBy('regular_price')->orderBy('sort_order'),
            'price_desc' => $query->orderByDesc('regular_price')->orderBy('sort_order'),
            'name_asc' => $query->orderBy('name')->orderBy('sort_order'),
            'featured' => $query->orderByDesc('is_featured')->orderBy('sort_order'),
            default => $query->orderBy('sort_order'),
        };

        return $query->paginate(24)->withQueryString();
    }

    public function categories(): array
    {
        return Category::query()
            ->where('is_addon', false)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'full_path', 'parent_id', 'is_addon', 'is_brand_group', 'product_count', 'sort_order'])
            ->toArray();
    }

    public function brands(): array
    {
        return Brand::query()->orderBy('name')->get(['id', 'name', 'slug', 'logo_url'])->toArray();
    }

    public function brandsWithCounts(array $filters): array
    {
        $query = Product::query()->where('is_addon', false)->where('is_price_outlier', false)->whereNull('deleted_at');
        $this->applyFacetFilters($query, $filters, false);

        $counts = $query->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as product_count')
            ->groupBy('brand_id')
            ->pluck('product_count', 'brand_id');

        return Brand::query()->orderBy('name')->get(['id', 'name', 'slug', 'logo_url'])
            ->map(fn (Brand $brand): array => [...$brand->toArray(), 'product_count' => (int) ($counts[$brand->id] ?? 0)])
            ->values()
            ->all();
    }

    private function applyFacetFilters(Builder $query, array $filters, bool $includeBrand = true): void
    {
        if (($filters['category'] ?? null) !== null) {
            $query->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $this->descendantIds((string) $filters['category'])));
        }

        if ($includeBrand && ($filters['brand'] ?? null) !== null) {
            $query->whereHas('brand', fn (Builder $brandQuery) => $brandQuery->where('slug', $filters['brand']));
        }

        if (($filters['q'] ?? null) !== null) {
            $search = trim((string) $filters['q']);
            $query->where(fn (Builder $searchQuery) => $searchQuery->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        if (($filters['stock'] ?? false) === true) {
            $query->where('stock', '>', 0);
        }

        if (($filters['min'] ?? null) !== null) {
            $query->where('regular_price', '>=', (int) $filters['min']);
        }

        if (($filters['max'] ?? null) !== null) {
            $query->where('regular_price', '<=', (int) $filters['max']);
        }

        if (($filters['sort'] ?? null) === 'sale') {
            $query->whereNotNull('sale_price');
        }
    }

    public function descendantIds(string $category): array
    {
        $root = Category::query()
            ->where('slug', $category)
            ->orWhere('full_path', $category)
            ->first();

        if (! $root) {
            return [];
        }

        $categories = Category::query()->get(['id', 'parent_id']);
        $ids = [$root->id];
        $changed = true;

        while ($changed) {
            $changed = false;
            foreach ($categories as $candidate) {
                if ($candidate->parent_id !== null && in_array($candidate->parent_id, $ids, true) && ! in_array($candidate->id, $ids, true)) {
                    $ids[] = $candidate->id;
                    $changed = true;
                }
            }
        }

        return $ids;
    }
}
