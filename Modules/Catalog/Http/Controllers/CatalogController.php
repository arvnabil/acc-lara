<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\CatalogQuery;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\OrderItem;
use Modules\Review\Models\Review;
use Modules\SEO\Services\SeoResolver;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogQuery $catalog,
        private readonly SeoResolver $seoResolver,
    ) {}

    public function index(Request $request): Response
    {
        $request->validate([
            'kategori' => ['nullable', 'string', 'max:500'],
            'brand' => ['nullable', 'string', 'max:500'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:default,featured,price_asc,price_desc,name_asc,sale'],
            'stok' => ['nullable', 'boolean'],
            'min' => ['nullable', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0'],
            'halaman' => ['nullable', 'integer', 'min:1'],
        ]);

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
            'brands' => collect($this->catalog->brandsWithCounts($filters))->map(fn (array $brand): array => [...$brand, 'logo' => $brand['logo_url']])->all(),
            'filters' => $filters,
            'seo' => $this->seoResolver->staticPage('Katalog Produk', 'Temukan produk IT dan audio visual untuk kebutuhan bisnis dan korporasi.', url('/katalog'), (bool) $filters['q']),
        ]);
    }

    public function home(): Response
    {
        $base = fn () => Product::query()
            ->with(['brand:id,name,slug,logo_url', 'categories:id,name,slug,full_path,parent_id', 'images:id,product_id,url,sort_order', 'attributes:id,attributable_type,attributable_id,name,value', 'seo'])
            ->where('is_addon', false)
            ->where('is_price_outlier', false)
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
            'flashSaleProducts' => $base()->where('is_flash_sale', true)->limit(8)->get()->map(fn (Product $product): array => $this->serializeProduct($product))->values()->all(),
            'seo' => $this->seoResolver->staticPage('Belanja Produk IT & Audio Visual', 'Belanja produk IT dan audio visual original dengan garansi resmi untuk kebutuhan bisnis dan korporasi.', url('/')),
        ]);
    }

    public function show(string $slug): Response|SymfonyResponse
    {
        $product = Product::query()
            ->with([
                'brand:id,name,slug,logo_url',
                'categories:id,name,slug,full_path,parent_id',
                'images:id,product_id,url,sort_order',
                'seo',
                'attributes:id,attributable_type,attributable_id,name,value',
                'variants' => fn ($query) => $query
                    ->where('is_price_outlier', false)
                    ->with(['images:id,variant_id,url,sort_order', 'attributes:id,attributable_type,attributable_id,name,value']),
            ])
            ->where('is_addon', false)
            ->where('is_price_outlier', false)
            ->where(function ($query) use ($slug): void {
                $query->where('slug', $slug);

                if (ctype_digit($slug)) {
                    $query->orWhereKey((int) $slug);
                }
            })
            ->first();

        if (! $product) {
            return Inertia::render('Catalog::ProductNotFound', ['product' => null, 'variants' => [], 'related' => [], 'relatedProducts' => [], 'brands' => [], 'categories' => []])
                ->toResponse(request())
                ->setStatusCode(404);
        }

        $related = Product::query()
            ->with([
                'brand:id,name,slug,logo_url',
                'categories:id,name,slug,full_path,parent_id',
                'images:id,product_id,url,sort_order',
                'seo',
                'attributes:id,attributable_type,attributable_id,name,value',
            ])
            ->where('products.id', '<>', $product->id)
            ->where('is_addon', false)
            ->where('is_price_outlier', false)
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $product->categories->modelKeys()))
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $productData = $this->serializeProduct($product, true);
        $approvedReviews = Review::query()->with(['user:id,name', 'order:id,number'])->where('product_id', $product->id)->where('is_approved', true)->latest('id')->get();
        $aggregate = Review::query()->where('product_id', $product->id)->where('is_approved', true)->selectRaw('COUNT(*) as total_reviews, AVG(rating) as average_rating')->first();
        $productData['reviews'] = $approvedReviews->map(fn (Review $review): array => $this->serializeReview($review))->values()->all();
        $productData['review_summary'] = [
            'average' => $aggregate?->average_rating ? round((float) $aggregate->average_rating, 1) : null,
            'count' => (int) ($aggregate?->total_reviews ?? 0),
            'sold' => (int) OrderItem::query()->where('product_id', $product->id)->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Completed))->sum('quantity'),
        ];
        $productData['review_eligibility'] = $this->reviewEligibility($product->id, request()->user());

        return Inertia::render('Catalog::ProductDetailPage', [
            'product' => $productData,
            'variants' => $product->variants->map(fn ($variant): array => $this->serializeVariant($variant))->values()->all(),
            'related' => $related->map(fn (Product $relatedProduct): array => $this->serializeProduct($relatedProduct))->values()->all(),
            'relatedProducts' => $related->map(fn (Product $relatedProduct): array => $this->serializeProduct($relatedProduct))->values()->all(),
            'brands' => collect($this->catalog->brands())->map(fn (array $brand): array => [...$brand, 'logo' => $brand['logo_url']])->all(),
            'categories' => $this->catalog->categories(),
            'seo' => $this->seoResolver->for($product),
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
        $data['images'] = $product->images
            ->map(fn ($image): string => $this->imageUrl($image->url))
            ->values()
            ->all();
        $data['category_ids'] = $product->categories->modelKeys();
        $data['attributes'] = $product->attributes->toArray();
        $data['seo'] = $product->seo?->only(['title', 'description', 'og_image', 'canonical_url', 'noindex']);
        $data['points_estimate'] = intdiv(max(0, (int) ($product->sale_price ?: $product->regular_price)), 10000);

        if ($detail) {
            $data['variants'] = $product->variants->map(fn ($variant): array => $this->serializeVariant($variant))->values()->all();
        }

        return $data;
    }

    private function serializeVariant($variant): array
    {
        $data = $variant->toArray();
        $data['images'] = $variant->images
            ->map(fn ($image): string => $this->imageUrl($image->url))
            ->values()
            ->all();
        $data['attributes'] = $variant->attributes->toArray();
        $data['points_estimate'] = intdiv(max(0, (int) ($variant->sale_price ?: $variant->regular_price)), 10000);

        return $data;
    }

    private function imageUrl(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://', '/'])
            ? $path
            : Storage::disk('public')->url($path);
    }

    private function serializeReview(Review $review): array
    {
        $media = collect($review->media ?? []);

        return [
            'id' => $review->id,
            'orderId' => $review->order?->number,
            'rating' => (int) $review->rating,
            'comment' => $review->body,
            'isAnonymous' => (bool) $review->is_anonymous,
            'userName' => $review->is_anonymous ? 'Pengguna Anonim' : $review->user?->name,
            'date' => $review->created_at?->translatedFormat('d F Y'),
            'images' => $media->where('type', 'photo')->map(fn (array $item): string => Storage::disk('public')->url($item['path']))->values()->all(),
            'video' => ($video = $media->firstWhere('type', 'video')) ? Storage::disk('public')->url($video['path']) : null,
        ];
    }

    private function reviewEligibility(int $productId, ?User $user): array
    {
        if (! $user?->client_id) {
            return ['can_write' => false, 'items' => []];
        }

        $items = OrderItem::query()->with('order:id,number,status,client_id')->where('product_id', $productId)
            ->whereHas('order', fn ($query) => $query->where('client_id', $user->client_id)->where('status', OrderStatus::Completed))
            ->whereDoesntHave('reviews', fn ($query) => $query->where('client_id', $user->client_id))
            ->latest('id')->get();

        return [
            'can_write' => $items->isNotEmpty(),
            'items' => $items->map(fn (OrderItem $item): array => [
                'order_item_id' => $item->id,
                'order_id' => $item->order_id,
                'order_number' => $item->order->number,
            ])->values()->all(),
        ];
    }
}


