<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\PricingService;
use Modules\Cart\Services\BiteshipShippingService;
use Modules\Clients\Models\Address;
use Modules\Orders\Actions\CreateOrderAction;
use Modules\Orders\Models\Order;
use Modules\Cart\Models\CartItem;
use Modules\Promo\Models\PromoCode;
use Modules\Promo\Models\Voucher;
use Modules\Payments\Services\ManualPaymentService;
use Modules\Payments\Services\MidtransPaymentService;
use Modules\Payments\Models\PaymentSetting;

class OrderController
{
    public function midtransNotification(Request $request, MidtransPaymentService $service): \Illuminate\Http\JsonResponse
    {
        $service->handleNotification($request->validate([
            'order_id' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required'],
            'transaction_status' => ['required', 'string'],
            'signature_key' => ['required', 'string'],
            'payment_type' => ['nullable', 'string'],
            'channel' => ['nullable', 'string'],
            'fraud_status' => ['nullable', 'string'],
        ]));

        return response()->json(['success' => true]);
    }

    public function checkout(Request $request, PricingService $pricing, BiteshipShippingService $shipping): Response|RedirectResponse
    {
        $paymentSettings = PaymentSetting::getInstance();
        $cart = Cart::query()->where('user_id', $request->user()->id)->with(['items.product.images', 'items.variant.images', 'promoCode', 'voucher'])->first();
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart');
        }

        if (blank($cart->referral_code) && filled($request->user()->referredBy?->referral_code)) {
            $cart->update(['referral_code' => $request->user()->referredBy->referral_code]);
            $cart->refresh();
        }

        $defaultAddress = $request->user()->client?->addresses()->where('is_default', true)->first()
            ?? $request->user()->client?->addresses()->oldest('id')->first()
            ?? $request->user()->addresses()->where('is_default', true)->first();
        $shippingOptions = $shipping->optionsForAddress($defaultAddress, $cart->items);
        $quotes = $pricing->quoteForOptions($cart->items, $cart->promoCode, $shippingOptions, $cart->voucher);
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
        $initialQuote = $quotes[$shippingOptions[0]['id'] ?? ''] ?? [];
        $availableVouchers = Voucher::query()
            ->where('client_id', $request->user()->client_id)
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
            ])->values()->all();

        return Inertia::render('Catalog::CheckoutPage', [
            'initialStep' => 'checkout',
            'order' => null,
            'cart' => [
                'items' => $cart->items->map(fn (CartItem $item): array => $this->serializeCartItem($item))->values()->all(),
                'total' => $initialQuote['goods_subtotal'] ?? 0,
                'itemCount' => $cart->items->sum('quantity'),
                'pricing' => $initialQuote,
                'appliedPromo' => $appliedPromo,
                'referralCode' => $cart->referral_code,
            ],
            'quotes' => $quotes,
            'shippingOptions' => $shippingOptions,
            'paymentMethods' => $paymentSettings->methods(),
            'banks' => $paymentSettings->banks(),
            'promoCodes' => PromoCode::query()->where('is_active', true)->orderBy('code')->get(['code', 'label'])->values()->all(),
            'availableVouchers' => $availableVouchers,
        ]);
    }

    public function store(Request $request, CreateOrderAction $action, BiteshipShippingService $shipping): RedirectResponse
    {
        $data = $request->validate(['address_id' => ['nullable', 'integer'], 'shipping_address' => ['required', 'array'], 'shipping_address.recipient' => ['required', 'string'], 'shipping_address.phone' => ['required', 'string'], 'shipping_address.province' => ['required', 'string'], 'shipping_address.city' => ['required', 'string'], 'shipping_address.address' => ['required', 'string'], 'shipping_address.postal_code' => ['required', 'string'], 'shipping_address.destination_area_id' => ['nullable', 'string'], 'payment_method' => ['required', 'string'], 'shipping_method' => ['required', 'string'], 'shipping_label' => ['nullable', 'string'], 'customer_notes' => ['nullable', 'string']]);
        $data['payment_method'] = config('payment.legacy_method_aliases.'.$data['payment_method'], $data['payment_method']);
        $cart = Cart::query()->where('user_id', $request->user()->id)->firstOrFail();
        if (! $cart->items()->exists()) {
            return redirect()->route('cart')->with('error', 'Keranjang sudah kosong. Pesanan sebelumnya sudah dibuat atau item sudah tidak tersedia.');
        }

        $address = ! empty($data['address_id'])
            ? Address::query()->where(function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->orWhere('client_id', $request->user()->client_id);
            })->findOrFail($data['address_id'])
            : null;
        $shippingOptions = $shipping->optionsForAddressData($address ? [
            'province' => $address->province,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'district' => $address->district_name,
            'subdistrict' => $address->subdistrict_name,
            'destination_id' => $address->destination_id,
        ] : $data['shipping_address'], $cart->items()->with(['product', 'variant'])->get());
        $order = $action->execute($cart, $data, $shippingOptions);

        return redirect()->route('payment', $order->number);
    }

    public function payment(Request $request, string $number, MidtransPaymentService $midtrans): Response|RedirectResponse
    {
        $paymentSettings = PaymentSetting::getInstance();
        $order = $this->owned($request, $number);

        if ($order->payment_method === 'midtrans' && $order->status === \Modules\Orders\Enums\OrderStatus::PendingPayment) {
            $transaction = $order->paymentTransactions()
                ->where('provider', 'midtrans')
                ->latest('id')
                ->first();

            if ($transaction) {
                try {
                    $midtrans->checkStatus($transaction);
                    $order = $this->owned($request, $number);
                } catch (\Throwable) {
                    // Keep the payment page available when Midtrans is temporarily unreachable.
                }
            }
        }

        if ($order->status !== \Modules\Orders\Enums\OrderStatus::PendingPayment) {
            return redirect()->route('success', $number);
        }

        $paymentMethods = array_values(array_filter(
            $paymentSettings->methods(),
            fn (array $method): bool => $method['id'] === $order->payment_method,
        ));

        return Inertia::render('Catalog::CheckoutPage', ['initialStep' => 'payment', 'order' => $order, 'shippingOptions' => config('shipping.options'), 'paymentMethods' => $paymentMethods, 'banks' => $paymentSettings->banks(), 'qris' => $paymentSettings->qris()]);
    }

    public function midtrans(Request $request, string $number, MidtransPaymentService $service): SymfonyResponse
    {
        $order = $this->owned($request, $number);
        abort_unless($order->payment_method === 'midtrans', 422, 'Metode pembayaran order bukan Midtrans.');

        $transaction = $service->createTransaction($order->load(['items', 'user']));

        return Inertia::location((string) $transaction->redirect_url);
    }

    public function paid(Request $request, string $number, MidtransPaymentService $service, ManualPaymentService $manualPayment): RedirectResponse
    {
        $order = $this->owned($request, $number);
        abort_unless($order->status->value === 'pending_payment', 422);

        if ($order->payment_method === 'midtrans') {
            $transaction = $order->paymentTransactions()->where('provider', 'midtrans')->latest('id')->firstOrFail();
            $service->checkStatus($transaction);

            return redirect()->route('payment', $number)->with('payment_message', 'Pembayaran belum diterima Midtrans. Silakan selesaikan pembayaran atau coba lagi.');
        }

        $manualPayment->submit($order, (int) $request->user()->id);

        return redirect()->route('success', $number);
    }

    public function success(Request $request, string $number): Response
    {
        $paymentSettings = PaymentSetting::getInstance();

        return Inertia::render('Catalog::CheckoutPage', ['initialStep' => 'success', 'order' => $this->owned($request, $number), 'shippingOptions' => config('shipping.options'), 'paymentMethods' => $paymentSettings->methods(), 'banks' => $paymentSettings->banks(), 'qris' => $paymentSettings->qris()]);
    }

    public function track(Request $request): Response
    {
        $order = null;
        if ($request->filled('number')) {
            $record = Order::query()
                ->where('number', $request->string('number'))
                ->where('user_id', $request->user()->id)
                ->with('items')
                ->first();
            if ($record) {
                app(BiteshipShippingService::class)->syncTracking($record);
                $record->refresh()->load('items');
            }
            $order = $record ? $this->serializeTrackedOrder($record) : null;
        }

        return Inertia::render('Catalog::LacakPesananPage', ['order' => $order]);
    }

    /** @return array<string, mixed> */
    private function serializeTrackedOrder(Order $order): array
    {
        return [
            'id' => $order->number,
            'number' => $order->number,
            'date' => $order->created_at?->format('d F Y'),
            'status' => match ($order->status?->value) {
                'pending_payment' => 'Menunggu Pembayaran',
                'awaiting_verification' => 'Menunggu Verifikasi',
                'paid' => 'Dibayar',
                'processing' => 'Diproses',
                'packed' => 'Dikemas',
                'shipped' => 'Dalam Pengiriman',
                'completed' => 'Selesai',
                'returned' => 'Dikembalikan',
                'cancelled' => 'Dibatalkan',
                default => $order->status?->value,
            },
            'total' => (int) $order->grand_total,
            'title' => $order->items->first()?->product_name ?? 'Pesanan Accommerce',
            'shippingEstimate' => $order->shipping_estimate,
            'shippingLabel' => $order->shipping_label,
            'trackingNumber' => $order->tracking_number,
            'biteshipTrackingId' => $order->biteship_tracking_id,
            'biteshipWaybillId' => $order->biteship_waybill_id,
            'biteshipStatus' => $order->biteship_status,
            'biteshipTrackingUrl' => $order->biteship_tracking_url,
            'biteshipTrackingHistory' => $order->biteship_tracking_history,
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'qty' => (int) $item->quantity,
                'price' => (int) $item->unit_price,
            ])->values()->all(),
        ];
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
            'image' => $this->imageUrl($item->variant?->images->first()?->url ?? $item->product->images->first()?->url),
            'quantity' => $item->quantity,
            'rentalInfo' => null,
            'variant' => $item->variant?->toArray(),
        ];
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
