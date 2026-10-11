<?php

namespace Modules\Cart\Services;

use Modules\Promo\Models\PromoCode;

class PricingService
{
    /** @return array<string, int|bool> */
    public function calculate(iterable $items, ?PromoCode $promo = null, int $shipping = 350000): array
    {
        $subtotal = 0;
        $dummy = false;

        foreach ($items as $item) {
            $price = $item->variant?->sale_price ?: $item->variant?->regular_price ?: ($item->product->sale_price ?: $item->product->regular_price);
            if ($item->rental_start && $item->rental_end) {
                $price *= max(1, $item->rental_start->diffInDays($item->rental_end));
            }
            $subtotal += (int) $price * $item->quantity;
            $dummy = $dummy || (bool) ($item->variant?->is_dummy_price ?? $item->product->is_dummy_price);
        }

        $discount = 0;
        if ($promo) {
            $discount = $promo->type === 'percent' ? (int) round($subtotal * $promo->value / 100) : min((int) $promo->value, $subtotal);
            $discount = min($discount, $subtotal);
        }

        $net = max(0, $subtotal - $discount);
        $ppn = (int) round($subtotal * 0.11);
        $adminFee = 15000;

        return [
            'goods_subtotal' => $subtotal,
            'discount_on_goods' => $discount,
            'ppn' => $ppn,
            'shipping_estimate' => $shipping,
            'admin_fee' => $adminFee,
            'grand_total' => $net + $ppn + $shipping + $adminFee,
            'has_dummy_price_item' => $dummy,
            'estimated_points' => intdiv($net, 10000),
        ];
    }
}
