<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'kategori', 'brand', 'sort', 'stok', 'min', 'max']);

        return Inertia::render('Catalog::Index', [
            'products' => Product::query()
                ->with(['brand:id,name,slug', 'category:id,name,full_path'])
                ->filter($filters)
                ->sortBy($filters['sort'] ?? null)
                ->paginate(24, ['*'], 'halaman')
                ->withQueryString(),
            'categories' => Inertia::optional(fn () => Category::where('is_addon', false)->orderBy('sort_order')->get()),
            'brands' => Inertia::optional(fn () => Brand::orderBy('name')->get()),
            'filters' => $filters,
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::with(['brand', 'category'])->where('slug', $slug)->firstOrFail();

        return Inertia::render('Catalog::Show', [
            'product' => $product,
            'related' => Product::where('category_id', $product->category_id)
                ->whereKeyNot($product->id)->limit(4)->get(),      // Detail Produk memakai 4
        ]);
    }
}
