<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\SEO\Models\SeoMeta;
use JsonException;

class ImportProductSeoMetadata extends Command
{
    protected $signature = 'seo:import-product-meta
        {path=Front/src/data/wc-data-export.json : Path to the WooCommerce JSON export}
        {--force : Replace existing SEO values instead of filling only empty fields}';

    protected $description = 'Import WooCommerce/Yoast SEO metadata into product SEO records';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! is_file($path)) {
            $this->error("Export file tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        try {
            $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('Export JSON tidak valid: '.$exception->getMessage());
            return self::FAILURE;
        }

        if (! is_array($rows)) {
            $this->error('Format export harus berupa array produk.');
            return self::FAILURE;
        }

        $rowsBySku = collect($rows)->filter(fn (array $row): bool => filled($row['SKU'] ?? null))->keyBy(fn (array $row): string => $this->normalize((string) $row['SKU']));
        $rowsByName = collect($rows)->filter(fn (array $row): bool => filled($row['Nama'] ?? null))->keyBy(fn (array $row): string => $this->normalize((string) $row['Nama']));
        $stats = ['matched_sku' => 0, 'matched_name' => 0, 'updated' => 0, 'skipped' => 0];

        Product::query()->with(['seo', 'images'])->orderBy('id')->lazyById(100)->each(function (Product $product) use ($rowsBySku, $rowsByName, &$stats): void {
            $row = filled($product->sku) ? $rowsBySku->get($this->normalize($product->sku)) : null;
            $matchType = $row ? 'sku' : null;

            if (! $row) {
                $row = $rowsByName->get($this->normalize($product->name));
                $matchType = $row ? 'name' : null;
            }

            if (! $row) {
                $stats['skipped']++;
                return;
            }

            $seo = $product->seo ?: new SeoMeta;
            $changed = false;
            foreach ($this->seoAttributes($product, $row) as $field => $value) {
                if ($this->option('force') || blank($seo->{$field})) {
                    $seo->{$field} = $value;
                    $changed = true;
                }
            }

            if ($changed) {
                $product->seo()->save($seo);
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }

            $stats['matched_'.$matchType]++;
        });

        $this->table(['Metric', 'Count'], [
            ['Matched by SKU', $stats['matched_sku']],
            ['Matched by name', $stats['matched_name']],
            ['SEO records updated', $stats['updated']],
            ['Skipped', $stats['skipped']],
        ]);
        $this->info('Import metadata SEO selesai.');
        return self::SUCCESS;
    }

    /** @param array<string, mixed> $row */
    private function seoAttributes(Product $product, array $row): array
    {
        $title = $this->cleanText($row['Meta:_yoast_wpseo_title'] ?? null);
        $description = $this->cleanText($row['Meta:_yoast_wpseo_metadesc'] ?? null);
        $keywords = collect([$this->cleanText($row['Meta:_yoast_wpseo_focuskw'] ?? null), ...$this->decodeKeywords($row['Meta:_yoast_wpseo_keywordsynonyms'] ?? null)])->filter()->unique()->values()->all();
        $fallbackDescription = $this->cleanText($product->short_description ?: $product->description);

        return [
            'title' => $title ?: $product->name,
            'description' => $description ?: $fallbackDescription,
            'keywords' => $keywords,
            'og_title' => $title ?: $product->name,
            'og_description' => $description ?: $fallbackDescription,
            'og_image' => $product->images->first()?->url,
            'canonical_url' => route('product.show', ['slug' => $product->slug], false),
        ];
    }

    /** @return array<int, string> */
    private function decodeKeywords(mixed $value): array
    {
        if (blank($value)) {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return collect($decoded)->map(fn (mixed $keyword): string => trim((string) $keyword))->filter()->all();
        }
        return collect(explode(',', (string) $value))->map(fn (string $keyword): string => trim($keyword))->filter()->all();
    }

    private function cleanText(mixed $value): ?string
    {
        $value = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '' || str_contains($value, '%%')) {
            return null;
        }
        return Str::limit($value, 500, '');
    }

    private function normalize(string $value): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $value) ?: $value));
    }
}
