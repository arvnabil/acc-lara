<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function catalogProduct(array $attributes = []): Product
{
    return Product::factory()->create($attributes);
}

test('catalog category filter includes descendants and excludes addons', function (): void {
    $root = Category::create(['name' => 'Root', 'slug' => 'root', 'sort_order' => 1]);
    $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id, 'sort_order' => 2]);
    $included = catalogProduct(['name' => 'Included']);
    $excluded = catalogProduct(['name' => 'Excluded']);
    $included->categories()->attach($child);
    $excluded->categories()->attach(Category::create(['name' => 'Other', 'slug' => 'other']));

    $this->get('/katalog?kategori=root')->assertOk()->assertInertia(fn ($page) => $page
        ->where('products.total', 1)
        ->where('products.data.0.name', 'Included'));
});

test('catalog filters brand query stock and regular price', function (): void {
    $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
    catalogProduct(['name' => 'Acme Camera', 'sku' => 'AC-1', 'brand_id' => $brand->id, 'stock' => 5, 'regular_price' => 50000]);
    catalogProduct(['name' => 'Acme Empty', 'sku' => 'AC-2', 'brand_id' => $brand->id, 'stock' => 0, 'regular_price' => 60000]);

    $this->get('/katalog?brand=acme&q=AC-1&stok=1&min=40000&max=50000')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('products.total', 1));
});

test('catalog pagination clamps and uses sort order as tie breaker', function (): void {
    foreach (['Bravo', 'Alpha', 'Charlie'] as $sortOrder => $name) {
        catalogProduct(['name' => $name, 'sort_order' => $sortOrder, 'regular_price' => 100000]);
    }

    $this->get('/katalog?halaman=99&sort=price_asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.page', 1)
            ->where('products.data.0.name', 'Bravo'));
});

test('addon products are excluded and missing product returns 404 page', function (): void {
    catalogProduct(['is_addon' => true]);

    $this->get('/katalog')->assertInertia(fn ($page) => $page->where('products.total', 0));
    $this->get('/produk/not-found')->assertStatus(404)->assertInertia(fn ($page) => $page->component('Catalog::ProductNotFound'));
});

test('product detail excludes outlier variants and returns four related products', function (): void {
    $category = Category::create(['name' => 'Detail', 'slug' => 'detail']);
    $product = catalogProduct(['slug' => 'main-product']);
    $product->categories()->attach($category);
    Variant::factory()->for($product)->create(['is_price_outlier' => false]);
    Variant::factory()->for($product)->create(['is_price_outlier' => true]);
    Product::factory()->count(5)->create()->each(fn (Product $related) => $related->categories()->attach($category));

    $this->get('/produk/main-product')->assertOk()->assertInertia(fn ($page) => $page
        ->where('variants', fn ($variants) => count($variants) === 1)
        ->where('related', fn ($related) => count($related) === 4));
});
