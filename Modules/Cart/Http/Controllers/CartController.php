<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Cart\Services\PricingService;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Modules\Promo\Services\PromoService;

class CartController extends Controller
{
    public function __construct(private readonly PricingService $pricing, private readonly PromoService $promos) {}

    public function show(Request $request): Response
    {
        $cart = $this->cart($request);
        $cart->load(['items.product.images', 'items.variant.images', 'promoCode']);
        $pricing = $this->pricing->calculate($cart->items, $cart->promoCode);

        $items = $cart->items->map(fn (CartItem $item): array => $this->serializeItem($item))->values()->all();

        return Inertia::render('Catalog::CartPage', [
            'cart' => ['items' => $items, 'total' => $pricing['goods_subtotal'], 'itemCount' => $cart->items->sum('quantity'), 'pricing' => $pricing, 'appliedPromo' => $cart->promoCode],
            'cart_summary' => [
                'goods_subtotal' => $pricing['goods_subtotal'],
                'shipping_estimate' => $pricing['shipping_estimate'],
                'discount_on_goods' => $pricing['discount_on_goods'],
                'grand_total' => $pricing['goods_subtotal'] + $pricing['shipping_estimate'] - $pricing['discount_on_goods'],
                'estimated_points' => $pricing['estimated_points'],
            ],
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'variant_id' => ['nullable', 'integer'], 'quantity' => ['required', 'integer', 'min:1'], 'rental_start' => ['nullable', 'date'], 'rental_end' => ['nullable', 'date'], 'rental_start_time' => ['nullable', 'date_format:H:i'], 'rental_end_time' => ['nullable', 'date_format:H:i']]);
        $product = Product::query()->findOrFail($data['product_id']);
        $variant = ! empty($data['variant_id']) ? Variant::query()->where('product_id', $product->id)->findOrFail($data['variant_id']) : null;
        $stock = $variant?->stock ?? $product->stock;
        $cart = $this->cart($request);
        $item = $cart->items()->where('product_id', $product->id)->where('variant_id', $variant?->id)->where('rental_start', $data['rental_start'] ?? null)->where('rental_end', $data['rental_end'] ?? null)->first();
        $quantity = min($stock, ($item?->quantity ?? 0) + $data['quantity']);
        $attributes = ['quantity' => $quantity, 'rental_start' => $data['rental_start'] ?? null, 'rental_end' => $data['rental_end'] ?? null, 'rental_start_time' => $data['rental_start_time'] ?? null, 'rental_end_time' => $data['rental_end_time'] ?? null];
        $item ? $item->update($attributes) : $cart->items()->create(['product_id' => $product->id, 'variant_id' => $variant?->id, ...$attributes]);

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart_id === $this->cart($request)->id, 403);
        $quantity = $request->validate(['quantity' => ['required', 'integer', 'min:1']])['quantity'];
        $stock = $item->variant?->stock ?? $item->product->stock;
        $item->update(['quantity' => min($stock, $quantity)]);

        return back();
    }

    public function remove(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart_id === $this->cart($request)->id, 403);
        $item->delete();

        return back();
    }

    public function applyPromo(Request $request): RedirectResponse
    {
        $cart = $this->cart($request);
        $cart->load(['items.product', 'items.variant']);
        $data = $request->validate(['code' => ['required', 'string']]);
        $pricing = $this->pricing->calculate($cart->items);
        try {
            $validated = $this->promos->validate($data['code'], $pricing['goods_subtotal']);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }
        $cart->update(['promo_code_id' => $validated['promo']->id]);

        return back()->with('success', "✅ Kode {$validated['promo']->code} berhasil diterapkan! ({$validated['promo']->label})");
    }

    public function removePromo(Request $request): RedirectResponse
    {
        $this->cart($request)->update(['promo_code_id' => null]);

        return back();
    }

    private function cart(Request $request): Cart
    {
        return $request->user()
            ? Cart::firstOrCreate(['user_id' => $request->user()->id])
            : tap(Cart::firstOrCreate(['session_id' => $request->session()->getId()]), function (): void {
                request()->session()->put('guest_cart_session_id', request()->session()->getId());
            });
    }

    /** @return array<string, mixed> */
    private function serializeItem(CartItem $item): array
    {
        return ['key' => (string) $item->id, 'id' => $item->id, 'product_id' => $item->product_id, 'variant_id' => $item->variant_id, 'sku' => $item->variant?->sku ?? $item->product->sku, 'name' => $item->variant?->name ?? $item->product->name, 'price' => $item->variant?->sale_price ?: $item->variant?->regular_price ?: ($item->product->sale_price ?: $item->product->regular_price), 'image' => $item->variant?->images->first()?->url ?? $item->product->images->first()?->url, 'quantity' => $item->quantity, 'stock' => $item->variant?->stock ?? $item->product->stock, 'rentalInfo' => $item->rental_start ? ['startDate' => $item->rental_start->toDateString(), 'endDate' => $item->rental_end?->toDateString(), 'startTime' => $item->rental_start_time, 'endTime' => $item->rental_end_time] : null, 'variant' => $item->variant?->toArray()];
    }
}
