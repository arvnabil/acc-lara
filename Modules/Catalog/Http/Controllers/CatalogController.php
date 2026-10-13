<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\CatalogQuery;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogQuery $catalog) {}

    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->string('kategori')->trim()->value() ?: null,
            'brand' => $request->string('brand')->trim()->value() ?: null,
            'q' => $request->string('q')->trim()->value() ?: null,
            'sort' => $request->string('sort')->trim()->value() ?: null,
            'stock' => $request->boolean('stok'),
            'min' => $request->filled('min') ? $request->integer('min') : null,
            'max' => $request->filled('max') ? $request->integer('max') : null,
        ];

        $products = $this->catalog->products($filters);

        return Inertia::render('Catalog::CatalogPage', [
            'products' => [
                'data' => collect($products->items())->map(fn (Product $product): array => $this->serializeProduct($product))->values()->all(),
                'total' => $products->total(),
                'page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total_pages' => $products->lastPage(),
            ],
            'categories' => $this->catalog->categories(),
            'brands' => collect($this->catalog->brands())->map(fn (array $brand): array => [...$brand, 'logo' => $brand['logo_url']])->all(),
            'filters' => $filters,
        ]);
    }

    public function home(): Response
    {
        $base = fn () => Product::query()
            ->with(['brand:id,name,slug,logo_url', 'categories:id,name,slug,full_path,parent_id', 'images:id,product_id,url,sort_order', 'attributes:id,attributable_type,attributable_id,name,value'])
            ->where('is_addon', false)
            ->orderBy('sort_order');

        return Inertia::render('Catalog::HomePage', [
            'categories' => collect($this->catalog->categories())
                ->where('parent_id', null)
                ->where('is_brand_group', false)
                ->take(8)
                ->values()
                ->all(),
            'brands' => collect($this->catalog->brands())->map(fn (array $brand): array => [...$brand, 'logo' => $brand['logo_url']])->take(9)->values()->all(),
            'featuredProducts' => $base()->where('is_featured', true)->limit(8)->get()->map(fn (Product $product): array => $this->serializeProduct($product))->values()->all(),
            'saleProducts' => $base()->whereNotNull('sale_price')->limit(8)->get()->map(fn (Product $product): array => $this->serializeProduct($product))->values()->all(),
            'flashSaleProducts' => [],
        ]);
    }

    public function show(string $slug): Response|HttpResponse
    {
        $product = Product::query()
            ->with([
                'brand:id,name,slug,logo_url',
                'categories:id,name,slug,full_path,parent_id',
                'images:id,product_id,url,sort_order',
                'attributes:id,attributable_type,attributable_id,name,value',
                'variants' => fn ($query) => $query
                    ->where('is_price_outlier', false)
                    ->with(['images:id,variant_id,url,sort_order', 'attributes:id,attributable_type,attributable_id,name,value']),
            ])
            ->where('is_addon', false)
            ->where('slug', $slug)
            ->first();

        if (! $product) {
            return Inertia::render('Catalog::ProductNotFound', ['product' => null, 'variants' => [], 'related' => [], 'relatedProducts' => [], 'brands' => [], 'categories' => []])
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $related = Product::query()
            ->with(['brand:id,name,slug,logo_url', 'images:id,product_id,url,sort_order'])
            ->where('products.id', '<>', $product->id)
            ->where('is_addon', false)
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $product->categories->modelKeys()))
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return Inertia::render('Catalog::ProductDetailPage', [
            'product' => $this->serializeProduct($product, true),
            'variants' => $product->variants->map(fn ($variant): array => $this->serializeVariant($variant))->values()->all(),
            'related' => $related->map(fn (Product $relatedProduct): array => $this->serializeProduct($relatedProduct))->values()->all(),
            'relatedProducts' => $related->map(fn (Product $relatedProduct): array => $this->serializeProduct($relatedProduct))->values()->all(),
            'brands' => collect($this->catalog->brands())->map(fn (array $brand): array => [...$brand, 'logo' => $brand['logo_url']])->all(),
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function search(): Response
    {
        return Inertia::render('Catalog::SearchPage', [
            'popularCategories' => collect($this->catalog->categories())->where('parent_id', null)->take(8)->values()->all(),
        ]);
    }

    public function categories(): Response
    {
        return Inertia::render('Catalog::CategoryMenuPage', [
            'categories' => $this->catalog->categories(),
        ]);
    }

    private function serializeProduct(Product $product, bool $detail = false): array
    {
        $data = $product->toArray();
        $data['images'] = $product->images->pluck('url')->values()->all();
        $data['category_ids'] = $product->categories->modelKeys();
        $data['attributes'] = $product->attributes->toArray();
        $data['points_estimate'] = intdiv(max(0, (int) ($product->sale_price ?: $product->regular_price)), 10000);

        if ($detail) {
            $data['variants'] = $product->variants->map(fn ($variant): array => $this->serializeVariant($variant))->values()->all();
        }

        return $data;
    }

    private function serializeVariant($variant): array
    {
        $data = $variant->toArray();
        $data['images'] = $variant->images->pluck('url')->values()->all();
        $data['attributes'] = $variant->attributes->toArray();
        $data['points_estimate'] = intdiv(max(0, (int) ($variant->sale_price ?: $variant->regular_price)), 10000);

        return $data;
    }
}
