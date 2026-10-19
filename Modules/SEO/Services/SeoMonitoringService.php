<?php

namespace Modules\SEO\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoMonitoringService
{
    public function sync(): int
    {
        $records = collect()
            ->concat($this->records(Product::query()->with('seo')->get(), 'Product'))
            ->concat($this->records(Brand::query()->with('seo')->get(), 'Brand'))
            ->concat($this->records(Category::query()->with('seo')->get(), 'Category'));

        DB::transaction(function () use ($records): void {
            SeoMonitoringRecord::query()->delete();

            if ($records->isNotEmpty()) {
                $now = now();
                $rows = $records->map(fn (array $record): array => [
                    ...$record,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                SeoMonitoringRecord::query()->insert($rows);
            }
        });

        return $records->count();
    }

    private function records(iterable $models, string $type): array
    {
        $records = [];

        foreach ($models as $model) {
            $url = $this->urlFor($model, $type);
            $seo = $model->seo;
            $hasTitle = filled($seo?->title);
            $hasDescription = filled($seo?->description);
            $canonicalValid = blank($seo?->canonical_url) || $seo->canonical_url === $url;
            $score = ($hasTitle ? 40 : 0) + ($hasDescription ? 40 : 0) + ($canonicalValid ? 20 : 0);

            $records[] = [
                'url' => $url,
                'model' => $type,
                'model_id' => $model->getKey(),
                'is_noindex' => (bool) ($seo?->noindex ?? false),
                'in_sitemap' => false,
                'canonical_valid' => $canonicalValid,
                'seo_score' => $score,
                'last_modified' => $model->updated_at,
            ];
        }

        return $records;
    }

    private function urlFor(Product|Brand|Category $model, string $type): string
    {
        return match ($type) {
            'Product' => route('product.show', ['slug' => $model->slug], false),
            'Brand' => route('catalog', ['brand' => $model->slug], false),
            default => route('catalog', ['kategori' => $model->slug], false),
        };
    }
}
