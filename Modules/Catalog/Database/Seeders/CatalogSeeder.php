<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductAttribute;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\Models\Variant;
use Modules\Catalog\Models\VariantImage;
use Modules\Catalog\Models\VariantAttribute;

class CatalogSeeder extends Seeder
{
    private string $dataPath;

    public function __construct()
    {
        $this->dataPath = base_path('Front/src/data');
    }

    public function run(): void
    {
        $this->seedBrands();
        $this->seedCategories();
        $this->seedProducts();
        $this->seedVariants();
    }

    private function seedBrands(): void
    {
        $brands = json_decode(file_get_contents($this->dataPath . '/brands.json'), true);

        foreach ($brands as $row) {
            Brand::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'     => $row['name'],
                    'logo_url' => $row['logo'] ?? null,
                ]
            );
        }

        $this->command->info('Brands seeded: ' . count($brands));
    }

    private function seedCategories(): void
    {
        $categories = json_decode(file_get_contents($this->dataPath . '/categories.json'), true);

        // Use an id-map so we can resolve parent_id from source data
        $idMap = []; // source_id => local_id

        // First pass: insert root categories (parent_id null)
        foreach ($categories as $row) {
            if ($row['parent_id'] === null) {
                $cat = Category::updateOrCreate(
                    ['slug' => $row['slug']],
                    [
                        'name'          => $row['name'],
                        'full_path'     => $row['full_path'] ?? $row['name'],
                        'parent_id'     => null,
                        'is_addon'      => $row['is_addon'] ?? false,
                        'is_brand_group' => $row['is_brand_group'] ?? false,
                        'product_count' => $row['product_count'] ?? 0,
                        'sort_order'    => $row['sort_order'] ?? 0,
                    ]
                );
                $idMap[$row['id']] = $cat->id;
            }
        }

        // Second pass: insert children
        foreach ($categories as $row) {
            if ($row['parent_id'] !== null) {
                $parentId = $idMap[$row['parent_id']] ?? null;
                $cat = Category::updateOrCreate(
                    ['slug' => $row['slug']],
                    [
                        'name'          => $row['name'],
                        'full_path'     => $row['full_path'] ?? $row['name'],
                        'parent_id'     => $parentId,
                        'is_addon'      => $row['is_addon'] ?? false,
                        'is_brand_group' => $row['is_brand_group'] ?? false,
                        'product_count' => $row['product_count'] ?? 0,
                        'sort_order'    => $row['sort_order'] ?? 0,
                    ]
                );
                $idMap[$row['id']] = $cat->id;
            }
        }

        $this->command->info('Categories seeded: ' . count($categories));

        // Store as cache for product seeding
        $this->categoryIdMap = $idMap;
    }

    private array $categoryIdMap = [];

    private function seedProducts(): void
    {
        $products = json_decode(file_get_contents($this->dataPath . '/products-index.json'), true);

        // Build brand slug→id map
        $brandMap = Brand::pluck('id', 'slug')->toArray();
        // Also map by id (source brand_id might be sequential)
        $brandIdMap = Brand::pluck('id', 'id')->toArray(); // local id map
        $sourceBrands = json_decode(file_get_contents($this->dataPath . '/brands.json'), true);
        $sourceBrandMap = []; // source_id => local_id
        foreach ($sourceBrands as $sb) {
            $local = Brand::where('slug', $sb['slug'])->value('id');
            if ($local) {
                $sourceBrandMap[$sb['id']] = $local;
            }
        }

        // Build category source_id → local_id map
        $sourceCategories = json_decode(file_get_contents($this->dataPath . '/categories.json'), true);
        $catIdMap = [];
        foreach ($sourceCategories as $sc) {
            $local = Category::where('slug', $sc['slug'])->value('id');
            if ($local) {
                $catIdMap[$sc['id']] = $local;
            }
        }

        $bar = $this->command->getOutput()->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $row) {
            $localBrandId = isset($row['brand_id']) ? ($sourceBrandMap[$row['brand_id']] ?? null) : null;

            $product = Product::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'sku'              => $row['sku'] ?? null,
                    'name'             => $row['name'],
                    'type'             => $row['type'] ?? 'simple',
                    'product_type'     => 'regular',
                    'regular_price'    => $row['regular_price'] ?? 0,
                    'sale_price'       => $row['sale_price'] ?? null,
                    'is_dummy_price'   => $row['is_dummy_price'] ?? false,
                    'is_price_outlier' => false,
                    'is_featured'      => $row['is_featured'] ?? false,
                    'is_flash_sale'    => false,
                    'is_addon'         => $row['is_addon'] ?? false,
                    'stock'            => $row['stock'] ?? 0,
                    'brand_id'         => $localBrandId,
                    'sort_order'       => $row['sort_order'] ?? 0,
                    'source_id'        => (string) ($row['id'] ?? null),
                ]
            );

            // Images — comma-separated in source
            if (!empty($row['images'])) {
                foreach ($row['images'] as $imgBlock) {
                    $urls = array_map('trim', explode(',', $imgBlock));
                    foreach (array_values($urls) as $order => $url) {
                        if (!$url) {
                            continue;
                        }
                        ProductImage::firstOrCreate(
                            ['product_id' => $product->id, 'url' => $url],
                            ['sort_order' => $order]
                        );
                    }
                }
            }

            // Categories pivot
            if (!empty($row['category_ids'])) {
                $localCatIds = array_filter(
                    array_map(fn($sid) => $catIdMap[$sid] ?? null, $row['category_ids'])
                );
                $product->categories()->syncWithoutDetaching($localCatIds);
            }

            // Attributes
            if (!empty($row['attributes'])) {
                foreach ($row['attributes'] as $attr) {
                    ProductAttribute::firstOrCreate([
                        'attributable_type' => Product::class,
                        'attributable_id'   => $product->id,
                        'name'              => $attr['name'],
                        'value'             => $attr['value'],
                    ]);
                }
            }

            // Initial stock movement (seeder reason) — only if product is new
            if ($product->wasRecentlyCreated && ($row['stock'] ?? 0) > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'delta'      => $row['stock'],
                    'reason'     => 'seeder',
                    'note'       => 'Initial stock from seeder',
                    'created_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Products seeded: ' . count($products));
    }

    private function seedVariants(): void
    {
        $variants = json_decode(file_get_contents($this->dataPath . '/variants.json'), true);

        // Six source rows (226-231) omit product_id. Their names identify the
        // two parent products unambiguously in products-index.json.
        $orphanProductMap = [
            226 => 530, 227 => 530, 228 => 530,
            229 => 531, 230 => 531, 231 => 531,
        ];

        // Map product source_id → local product id
        $productSourceMap = Product::whereNotNull('source_id')->pluck('id', 'source_id')->toArray();

        $bar = $this->command->getOutput()->createProgressBar(count($variants));
        $bar->start();

        foreach ($variants as $row) {
            $sourceProductId = isset($row['product_id'])
                ? (string) $row['product_id']
                : (isset($orphanProductMap[$row['id']]) ? (string) $orphanProductMap[$row['id']] : null);
            $localProductId = $sourceProductId ? ($productSourceMap[$sourceProductId] ?? null) : null;
            if (!$localProductId) {
                $bar->advance();
                continue;
            }

            $variant = Variant::updateOrCreate(
                ['product_id' => $localProductId, 'source_id' => (string) $row['id']],
                [
                    'sku'              => $row['sku'] ?? null,
                    'name'             => $row['name'],
                    'regular_price'    => $row['regular_price'] ?? 0,
                    'sale_price'       => $row['sale_price'] ?? null,
                    'is_dummy_price'   => $row['is_dummy_price'] ?? false,
                    'is_price_outlier' => $row['is_price_outlier'] ?? false,
                    'stock'            => $row['stock'] ?? 0,
                ]
            );

            // Images
            if (!empty($row['images'])) {
                foreach ($row['images'] as $url) {
                    if (!$url) {
                        continue;
                    }
                    VariantImage::firstOrCreate([
                        'variant_id' => $variant->id,
                        'url'        => $url,
                    ], ['sort_order' => 0]);
                }
            }

            // Attributes
            if (!empty($row['attributes'])) {
                foreach ($row['attributes'] as $attr) {
                    ProductAttribute::firstOrCreate([
                        'attributable_type' => Variant::class,
                        'attributable_id'   => $variant->id,
                        'name'              => $attr['name'],
                        'value'             => $attr['value'],
                    ]);
                }
            }

            // Initial stock movement for new variants
            if ($variant->wasRecentlyCreated && ($row['stock'] ?? 0) > 0) {
                StockMovement::create([
                    'product_id' => $localProductId,
                    'variant_id' => $variant->id,
                    'delta'      => $row['stock'],
                    'reason'     => 'seeder',
                    'note'       => 'Initial stock from seeder',
                    'created_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Variants seeded: ' . count($variants));
    }
}
