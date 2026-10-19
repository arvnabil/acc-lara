<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Cart\Services\PricingService;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;
use Modules\Promo\Services\PromoService;
use Modules\Promo\Models\PromoCode;
use Modules\Promo\Models\Voucher;
use App\Models\User;

class CartController extends Controller
{
    public function __construct(private readonly PricingService $pricing, private readonly PromoService $promos) {}

    public function show(Request $request): Response
    {
        $cart = $this->cart($request);
        $cart->load(['items.product.images', 'items.variant.images', 'promoCode', 'voucher']);
        $pricing = $this->pricing->calculate($cart->items, $cart->promoCode, (int) config('shipping.cart_estimate', 0), $cart->voucher);

        $items = $cart->items->map(fn (CartItem $item): array => $this->serializeItem($item))->values()->all();
        $appliedPromo = $cart->promoCode
            ? [
                'code' => $cart->promoCode->code,
                'label' => $cart->promoCode->label,
                'type' => 'promo',
            ]
            : ($cart->voucher
                ? [
                    'code' => $cart->voucher->code,
                    'label' => $cart->voucher->title,
                    'type' => 'voucher',
                ]
                : null);
        $availableVouchers = Voucher::query()
            ->where('client_id', $request->user()?->client_id)
            ->whereNull('used_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('expires_at')
            ->get(['id', 'code', 'title', 'discount_value', 'min_order', 'expires_at'])
            ->map(fn (Voucher $voucher): array => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'title' => $voucher->title,
                'discount' => (int) $voucher->discount_value,
                'minOrder' => (int) $voucher->min_order,
                'expiry' => $voucher->expires_at?->format('d M Y'),
            ])
            ->values()
            ->all() ?? [];
        $availablePromoCodes = PromoCode::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'label'])
            ->map(fn (PromoCode $promo): array => [
                'code' => $promo->code,
                'label' => $promo->label,
            ])
            ->values()
            ->all();

        return Inertia::render('Catalog::CartPage', [
            'cart' => ['items' => $items, 'total' => $pricing['goods_subtotal'], 'itemCount' => $cart->items->sum('quantity'), 'pricing' => $pricing, 'appliedPromo' => $appliedPromo, 'referralCode' => $cart->referral_code],
            'cart_summary' => [
                'goods_subtotal' => $pricing['goods_subtotal'],
                'shipping_estimate' => $pricing['shipping_estimate'],
                'discount_on_goods' => $pricing['discount_on_goods'],
                'ppn' => $pricing['ppn'],
                'admin_fee' => $pricing['admin_fee'],
                'grand_total' => $pricing['grand_total'],
                'estimated_points' => $pricing['estimated_points'],
            ],
            'availableVouchers' => $availableVouchers,
            'availablePromoCodes' => $availablePromoCodes,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'variant_id' => ['nullable', 'integer'], 'quantity' => ['required', 'integer', 'min:1'], 'rental_start' => ['nullable', 'date'], 'rental_end' => ['nullable', 'date'], 'rental_start_time' => ['nullable', 'date_format:H:i'], 'rental_end_time' => ['nullable', 'date_format:H:i']]);
        $product = Product::query()->findOrFail($data['product_id']);
        $variant = ! empty($data['variant_id']) ? Variant::query()->where('product_id', $product->id)->findOrFail($data['variant_id']) : null;
        abort_if($variant?->is_dummy_price || $product->is_dummy_price, 422, 'Produk dengan harga estimasi hanya dapat dipesan melalui WhatsApp.');
        $stock = $variant?->stock ?? $product->stock;
        abort_if($stock < 1, 422, 'Produk sedang tidak tersedia karena stok habis.');
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
        abort_if($stock < 1, 422, 'Produk sedang tidak tersedia karena stok habis.');
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
        $cart->load(['items.product', 'items.variant', 'voucher']);
        $data = $request->validate(['code' => ['required', 'string']]);
        $pricing = $this->pricing->calculate($cart->items);
        try {
            $validated = $this->promos->validateForClient($data['code'], $pricing['goods_subtotal'], $request->user()->client, $request->user());
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }
        $cart->update(['promo_code_id' => $validated['promo']?->id, 'voucher_id' => $validated['voucher']?->id]);

        $applied = $validated['promo'] ?? $validated['voucher'];
        $label = $applied->label ?? $applied->title;

        return back()->with('success', "✅ Kode {$applied->code} berhasil diterapkan! ({$label})");
    }

    public function removePromo(Request $request): RedirectResponse
    {
        $this->cart($request)->update(['promo_code_id' => null, 'voucher_id' => null]);

        return back();
    }

    public function applyReferral(Request $request): RedirectResponse
    {
        $code = strtoupper(trim($request->validate(['code' => ['required', 'string', 'max:20']])['code']));
        $referrer = User::query()->where('referral_code', $code)->first();
        if (! $referrer) {
            throw ValidationException::withMessages(['code' => 'Kode referral tidak ditemukan.']);
        }
        if ($referrer->id === $request->user()->id) {
            throw ValidationException::withMessages(['code' => 'Kode referral sendiri tidak dapat digunakan.']);
        }

        if (filled($request->user()->referred_by_user_id) && (int) $request->user()->referred_by_user_id !== (int) $referrer->id) {
            throw ValidationException::withMessages(['code' => 'Kode referral sudah terikat ke akun referral sebelumnya.']);
        }

        if (blank($request->user()->referred_by_user_id)) {
            $request->user()->update(['referred_by_user_id' => $referrer->id]);
        }

        $this->cart($request)->update(['referral_code' => $code]);

        return back()->with('success', "Kode referral {$code} berhasil diterapkan.");
    }

    public function removeReferral(Request $request): RedirectResponse
    {
        $this->cart($request)->update(['referral_code' => null]);

        return back();
    }

    private function cart(Request $request): Cart
    {
        if ($request->user()) {
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
            $referrerCode = $request->user()->referredBy?->referral_code;

            if (blank($cart->referral_code) && filled($referrerCode)) {
                $cart->update(['referral_code' => $referrerCode]);
            }

            return $cart->fresh();
        }

        return tap(Cart::firstOrCreate(['session_id' => $request->session()->getId()]), function (): void {
            request()->session()->put('guest_cart_session_id', request()->session()->getId());
        });
    }

    /** @return array<string, mixed> */
    private function serializeItem(CartItem $item): array
    {
        $image = $item->variant?->images->first()?->url ?? $item->product->images->first()?->url;

        return ['key' => (string) $item->id, 'id' => $item->id, 'product_id' => $item->product_id, 'slug' => $item->product->slug, 'variant_id' => $item->variant_id, 'sku' => $item->variant?->sku ?? $item->product->sku, 'name' => $item->variant?->name ?? $item->product->name, 'price' => $item->variant?->sale_price ?: $item->variant?->regular_price ?: ($item->product->sale_price ?: $item->product->regular_price), 'image' => $this->imageUrl($image), 'quantity' => $item->quantity, 'stock' => $item->variant?->stock ?? $item->product->stock, 'rentalInfo' => $item->rental_start ? ['startDate' => $item->rental_start->toDateString(), 'endDate' => $item->rental_end?->toDateString(), 'startTime' => $item->rental_start_time, 'endTime' => $item->rental_end_time] : null, 'variant' => $item->variant?->toArray()];
    }

    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/'])
            ? $path
            : Storage::disk('public')->url($path);
    }
}


