<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\PricingService;
use Modules\Orders\Actions\CreateOrderAction;
use Modules\Orders\Models\Order;
use Modules\Cart\Models\CartItem;
use Modules\Promo\Models\PromoCode;

class OrderController
{
    public function checkout(Request $request, PricingService $pricing): Response|RedirectResponse
    {
        $cart = Cart::query()->where('user_id', $request->user()->id)->with(['items.product.images', 'items.variant.images', 'promoCode'])->first();
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart');
        }

        $quotes = $pricing->quote($cart->items, $cart->promoCode);

        return Inertia::render('Catalog::CheckoutPage', [
            'initialStep' => 'checkout',
            'order' => null,
            'cart' => [
                'items' => $cart->items->map(fn (CartItem $item): array => $this->serializeCartItem($item))->values()->all(),
                'total' => $quotes['kurir-khusus']['goods_subtotal'] ?? 0,
                'itemCount' => $cart->items->sum('quantity'),
                'pricing' => $quotes['kurir-khusus'] ?? [],
                'appliedPromo' => $cart->promoCode,
            ],
            'quotes' => $quotes,
            'shippingOptions' => config('shipping.options'),
            'paymentMethods' => config('payment.methods'),
            'banks' => config('payment.banks'),
            'promoCodes' => PromoCode::query()->where('is_active', true)->orderBy('code')->get(['code', 'label'])->values()->all(),
        ]);
    }

    public function store(Request $request, CreateOrderAction $action): RedirectResponse
    {
        $data = $request->validate(['shipping_address' => ['required', 'array'], 'shipping_address.recipient' => ['required', 'string'], 'shipping_address.phone' => ['required', 'string'], 'shipping_address.province' => ['required', 'string'], 'shipping_address.city' => ['required', 'string'], 'shipping_address.address' => ['required', 'string'], 'shipping_address.postal_code' => ['required', 'string'], 'payment_method' => ['required', 'string'], 'shipping_method' => ['required', 'string'], 'shipping_label' => ['nullable', 'string'], 'customer_notes' => ['nullable', 'string']]);
        $cart = Cart::query()->where('user_id', $request->user()->id)->firstOrFail();
        $order = $action->execute($cart, $data);

        return redirect()->route('payment', $order->number);
    }

    public function payment(Request $request, string $number): Response
    {
        return Inertia::render('Catalog::CheckoutPage', ['initialStep' => 'payment', 'order' => $this->owned($request, $number), 'shippingOptions' => config('shipping.options'), 'paymentMethods' => config('payment.methods'), 'banks' => config('payment.banks')]);
    }

    public function paid(Request $request, string $number): RedirectResponse
    {
        $order = $this->owned($request, $number);
        abort_unless($order->status->value === 'pending_payment', 422);
        $order->update(['status' => 'awaiting_verification']);
        $order->statusHistory()->create(['from_status' => 'pending_payment', 'to_status' => 'awaiting_verification', 'actor_id' => $request->user()->id, 'created_at' => now()]);

        return redirect()->route('success', $number);
    }

    public function success(Request $request, string $number): Response
    {
        return Inertia::render('Catalog::CheckoutPage', ['initialStep' => 'success', 'order' => $this->owned($request, $number), 'shippingOptions' => config('shipping.options'), 'paymentMethods' => config('payment.methods'), 'banks' => config('payment.banks')]);
    }

    public function track(Request $request): Response
    {
        $order = $request->filled('number') ? Order::query()->where('number', $request->string('number'))->where('user_id', $request->user()->id)->with('statusHistory')->first() : null;

        return Inertia::render('Catalog::LacakPesananPage', ['order' => $order]);
    }

    private function owned(Request $request, string $number): Order
    {
        return Order::query()->where('number', $number)->where('user_id', $request->user()->id)->with(['items', 'statusHistory'])->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCartItem(CartItem $item): array
    {
        return [
            'key' => (string) $item->id,
            'id' => $item->id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'sku' => $item->variant?->sku ?? $item->product->sku,
            'name' => $item->variant?->name ?? $item->product->name,
            'price' => $item->variant?->sale_price ?: $item->variant?->regular_price ?: ($item->product->sale_price ?: $item->product->regular_price),
            'image' => $item->variant?->images->first()?->url ?? $item->product->images->first()?->url,
            'quantity' => $item->quantity,
            'rentalInfo' => null,
            'variant' => $item->variant?->toArray(),
        ];
    }
}
