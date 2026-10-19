<?php

namespace Modules\SEO\Services\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\SEO\Models\SeoSetting;

class SeoService
{
    public function __construct(protected SeoManager $manager) {}

    public function for(Model $model): array
    {
        $model->loadMissing('seo');

        if ($model instanceof Product) {
            $model->loadMissing(['images', 'brand']);
        }

        $seo = $model->seo;
        $title = $seo?->title ?: ($model->name ?? config('app.name'));
        $description = Str::limit(strip_tags((string) ($seo?->description ?: ($model->short_description ?? $model->description ?? ''))), 160);
        $url = $this->url($model);
        $image = $seo?->og_image ?: $this->image($model);
        $pageSchema = [
            '@type' => 'WebPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $description,
            'inLanguage' => 'id-ID',
        ];

        if ($model instanceof Product) {
            $pageSchema['mainEntity'] = ['@id' => $url.'#product'];
            $pageSchema = [
                '@type' => 'Product',
                '@id' => $url.'#product',
                'name' => $model->name,
                'description' => $description,
                'image' => $model->images->map(fn ($image): string => $this->absoluteImage($image->url))->values()->all(),
                'sku' => $model->sku,
                'brand' => $model->brand?->name ? ['@type' => 'Brand', 'name' => $model->brand->name] : null,
                'offers' => [
                    '@type' => 'Offer',
                    'url' => $url,
                    'priceCurrency' => 'IDR',
                    'price' => $model->sale_price ?: $model->regular_price,
                    'availability' => (int) $model->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                ],
            ];
        } elseif ($model instanceof Brand) {
            $pageSchema['mainEntity'] = ['@type' => 'Brand', 'name' => $model->name];
        } elseif ($model instanceof Category) {
            $pageSchema['mainEntity'] = ['@type' => 'CollectionPage', 'name' => $model->name];
        }

        $override = $seo?->schema_override;
        if (is_string($override)) {
            $override = json_decode($override, true);
        }

        return [
            'tags' => [
                'title' => $title,
                'description' => $description,
                'canonical_url' => $seo?->canonical_url ?: $url,
                'og_title' => $seo?->og_title ?: $title,
                'og_description' => $seo?->og_description ?: $description,
                'og_image' => $image,
                'noindex' => (bool) $seo?->noindex,
            ],
            'schema' => $override ?: $this->manager->graph($pageSchema),
        ];
    }

    public function staticPage(string $title, string $description, string $url, bool $noindex = false): array
    {
        $settings = SeoSetting::query()->first();
        $siteTitle = $settings?->default_title ?: config('app.name');
        $description = Str::limit(strip_tags($description ?: (string) ($settings?->default_description ?? '')), 160);

        return [
            'tags' => [
                'title' => $title,
                'description' => $description,
                'canonical_url' => $url,
                'og_title' => $title,
                'og_description' => $description,
                'og_image' => $settings?->default_og_image ? asset('storage/'.$settings->default_og_image) : null,
                'noindex' => $noindex,
            ],
            'schema' => $this->manager->graph([
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $title,
                'description' => $description,
                'inLanguage' => 'id-ID',
            ]),
        ];
    }

    protected function url(Model $model): string
    {
        $prefix = $model instanceof Brand ? 'brand/' : ($model instanceof Category ? 'kategori/' : 'produk/');

        return url('/'.$prefix.ltrim((string) $model->slug, '/'));
    }

    protected function image(Model $model): ?string
    {
        $path = $model instanceof Product ? $model->images->first()?->url : ($model->logo_url ?? null);

        return $path ? $this->absoluteImage($path) : null;
    }

    protected function absoluteImage(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.$path);
    }
}
