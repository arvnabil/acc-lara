<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\PricingService;
use Modules\Catalog\Models\StockMovement;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Promo\Models\PromoCode;

class CreateOrderAction
{
    public function __construct(private readonly PricingService $pricing) {}

    /** @param array<string, mixed> $data */
    public function execute(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data): Order {
            $cart->load(['items.product', 'items.variant', 'promoCode', 'voucher']);
            abort_if($cart->items->isEmpty(), 422, 'Keranjang kosong.');

            $items = [];
            foreach ($cart->items as $item) {
                $product = $item->product_id ? $item->product()->lockForUpdate()->firstOrFail() : null;
                $variant = $item->variant_id ? $item->variant()->lockForUpdate()->firstOrFail() : null;
                $stockModel = $variant ?: $product;
                abort_if($stockModel->stock < $item->quantity, 422, 'Stok produk tidak mencukupi.');
                $price = $variant?->sale_price ?: $variant?->regular_price ?: ($product->sale_price ?: $product->regular_price);
                $days = $item->rental_start && $item->rental_end ? max(1, $item->rental_start->diffInDays($item->rental_end)) : 1;
                $unitPrice = (int) $price * $days;
                $items[] = compact('item', 'product', 'variant', 'stockModel', 'unitPrice', 'days');
            }

            $shipping = collect(config('shipping.options', []))->firstWhere('id', $data['shipping_method'] ?? null);
            abort_if(! $shipping, 422, 'Metode pengiriman tidak valid.');
            $lockedVoucher = $cart->voucher ? $cart->voucher()->lockForUpdate()->firstOrFail() : null;
            abort_if($lockedVoucher?->used_at !== null, 422, '❌ Kode promo tidak valid atau sudah kedaluwarsa.');
            $quote = $this->pricing->calculate($cart->items, $cart->promoCode, (int) $shipping['price'], $cart->voucher);
            $order = Order::create([
                'number' => 'TMP-'.bin2hex(random_bytes(8)),
                'user_id' => $cart->user_id,
                'company_id' => $cart->user->company_id,
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
                'shipping_method' => $data['shipping_method'] ?? null,
                'shipping_label' => $shipping['title'],
                'shipping_address' => $data['shipping_address'],
                'payment_method' => $data['payment_method'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            foreach ($items as $row) {
                $row['stockModel']->decrement('stock', $row['item']->quantity);
                StockMovement::create(['product_id' => $row['product']->id, 'variant_id' => $row['variant']?->id, 'delta' => -$row['item']->quantity, 'reason' => 'order', 'reference_id' => $order->id]);
                $order->items()->create(['product_id' => $row['product']->id, 'variant_id' => $row['variant']?->id, 'product_name' => $row['product']->name, 'variant_name' => $row['variant']?->name, 'sku' => $row['variant']?->sku ?? $row['product']->sku, 'unit_price' => $row['unitPrice'], 'quantity' => $row['item']->quantity, 'subtotal' => $row['unitPrice'] * $row['item']->quantity, 'is_dummy_price' => $row['variant']?->is_dummy_price ?? $row['product']->is_dummy_price, 'rental_start' => $row['item']->rental_start, 'rental_end' => $row['item']->rental_end, 'rental_days' => $row['days']]);
            }

            $order->update(['number' => 'ACC-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]);
            $order->statusHistory()->create(['to_status' => OrderStatus::PendingPayment->value, 'actor_id' => $cart->user_id, 'created_at' => now()]);
            if ($cart->promoCode) {
                PromoCode::whereKey($cart->promo_code_id)->lockForUpdate()->increment('used_count');
            }
            if ($cart->voucher) {
                $lockedVoucher->update(['used_at' => now()]);
            }
            $cart->items()->delete();
            $cart->update(['promo_code_id' => null, 'voucher_id' => null]);

            return $order->fresh(['items', 'statusHistory']);
        });
    }
}
