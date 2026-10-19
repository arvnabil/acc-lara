<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\PricingService;
use Modules\Catalog\Models\StockMovement;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Promo\Models\PromoCode;
use Modules\Email\Services\EmailNotificationService;
use App\Models\User;

class CreateOrderAction
{
    public function __construct(private readonly PricingService $pricing) {}

    /** @param array<string, mixed> $data */
    public function execute(Cart $cart, array $data, ?array $shippingOptions = null): Order
    {
        return DB::transaction(function () use ($cart, $data, $shippingOptions): Order {
            $cart->load(['items.product', 'items.variant', 'promoCode', 'voucher']);
            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Keranjang sudah kosong. Pesanan sebelumnya sudah dibuat atau item sudah tidak tersedia.']);
            }

            $items = [];
            foreach ($cart->items as $item) {
                if ($item->quantity < 1) {
                    throw ValidationException::withMessages(['stock' => "{$item->product->name} sudah tidak tersedia."]);
                }
                $product = $item->product_id ? $item->product()->lockForUpdate()->firstOrFail() : null;
                $variant = $item->variant_id ? $item->variant()->lockForUpdate()->firstOrFail() : null;
                abort_if($variant?->is_dummy_price || $product?->is_dummy_price, 422, 'Produk dengan harga estimasi hanya dapat dipesan melalui WhatsApp.');
                $stockModel = $variant ?: $product;
                if ($stockModel->stock < $item->quantity) {
                    $available = max(0, (int) $stockModel->stock);
                    $name = $variant?->name ?: $product?->name ?: 'Produk';
                    $message = $available === 0
                        ? "{$name} sudah habis terjual."
                        : "Stok {$name} tersisa {$available} unit.";

                    throw ValidationException::withMessages(['stock' => $message]);
                }
                $price = $variant?->sale_price ?: $variant?->regular_price ?: ($product->sale_price ?: $product->regular_price);
                $days = $item->rental_start && $item->rental_end ? max(1, $item->rental_start->diffInDays($item->rental_end)) : 1;
                $unitPrice = (int) $price * $days;
                $items[] = compact('item', 'product', 'variant', 'stockModel', 'unitPrice', 'days');
            }

            $shipping = collect($shippingOptions ?? config('shipping.options', []))->firstWhere('id', $data['shipping_method'] ?? null);
            abort_if(! $shipping, 422, 'Metode pengiriman tidak valid.');
            $lockedVoucher = $cart->voucher ? $cart->voucher()->lockForUpdate()->firstOrFail() : null;
            $referrer = $cart->referral_code ? User::query()->where('referral_code', $cart->referral_code)->first() : null;
            abort_if($referrer?->id === $cart->user_id, 422, 'Kode referral sendiri tidak dapat digunakan.');
            abort_if($lockedVoucher?->used_at !== null, 422, '❌ Kode promo tidak valid atau sudah kedaluwarsa.');
            $quote = $this->pricing->calculate($cart->items, $cart->promoCode, (int) $shipping['price'], $cart->voucher);
            $order = Order::create([
                'number' => 'TMP-'.bin2hex(random_bytes(8)),
                'user_id' => $cart->user_id,
                'client_id' => $cart->user->client_id,
                'status' => OrderStatus::PendingPayment,
                'payment_type' => 'direct',
                'goods_subtotal' => $quote['goods_subtotal'],
                'discount_on_goods' => $quote['discount_on_goods'],
                'ppn' => $quote['ppn'],
                'shipping_cost' => $quote['shipping_estimate'],
                'admin_fee' => $quote['admin_fee'],
                'grand_total' => $quote['grand_total'],
                'has_dummy_price_item' => $quote['has_dummy_price_item'],
                'promo_code_id' => $cart->promo_code_id,
                'voucher_id' => $cart->voucher_id,
                'referral_code' => $cart->referral_code,
                'referrer_user_id' => $referrer?->id,
                'shipping_method' => $data['shipping_method'] ?? null,
                'shipping_label' => $shipping['title'],
                'shipping_estimate' => $shipping['desc'] ?? null,
                'biteship_courier_code' => $shipping['courier'] ?? null,
                'biteship_courier_service_code' => $shipping['service'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'payment_method' => $data['payment_method'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            foreach ($items as $row) {
                $row['stockModel']->decrement('stock', $row['item']->quantity);
                StockMovement::create(['product_id' => $row['product']->id, 'variant_id' => $row['variant']?->id, 'delta' => -$row['item']->quantity, 'reason' => 'order', 'reference_id' => $order->id]);
                $order->items()->create(['product_id' => $row['product']->id, 'variant_id' => $row['variant']?->id, 'product_name' => $row['product']->name, 'variant_name' => $row['variant']?->name, 'sku' => $row['variant']?->sku ?? $row['product']->sku, 'unit_price' => $row['unitPrice'], 'quantity' => $row['item']->quantity, 'subtotal' => $row['unitPrice'] * $row['item']->quantity, 'is_dummy_price' => $row['variant']?->is_dummy_price ?? $row['product']->is_dummy_price, 'rental_start' => $row['item']->rental_start, 'rental_end' => $row['item']->rental_end, 'rental_days' => $row['days']]);
            }

            $order->update([
                'number' => 'ACC-'.now()->format('my').'-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT),
            ]);
            $order->statusHistory()->create(['to_status' => OrderStatus::PendingPayment->value, 'actor_id' => $cart->user_id, 'created_at' => now()]);
            if ($cart->promoCode) {
                PromoCode::whereKey($cart->promo_code_id)->lockForUpdate()->increment('used_count');
            }
            if ($cart->voucher) {
                $lockedVoucher->update(['used_at' => now()]);
            }
            $cart->items()->delete();
            $cart->update(['promo_code_id' => null, 'voucher_id' => null, 'referral_code' => null]);

            $order = $order->fresh(['items', 'statusHistory']);
            DB::afterCommit(fn (): mixed => app(EmailNotificationService::class)->orderCreated($order));

            return $order;
        });
    }
}

