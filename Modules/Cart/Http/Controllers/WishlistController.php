<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Models\Wishlist;

class WishlistController
{
    public function index(Request $request): Response
    {
        $products = $request->user()->wishlists()->with('product.images')->get()->pluck('product')->filter()->values();

        return Inertia::render('Catalog::WishlistPage', ['wishlistProducts' => $products]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);
        $wishlist = Wishlist::query()->where('user_id', $request->user()->id)->where('product_id', $data['product_id'])->first();
        $wishlist ? $wishlist->delete() : Wishlist::create(['user_id' => $request->user()->id, 'product_id' => $data['product_id']]);

        return back();
    }
}
