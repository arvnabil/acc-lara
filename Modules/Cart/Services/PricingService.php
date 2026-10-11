<?php

namespace Modules\Cart\Services;

use Modules\Promo\Models\PromoCode;
use Modules\Promo\Models\Voucher;

class PricingService
{
    /** @return array<string, array<string, int|bool>> */
    public function quote(iterable $items, ?PromoCode $promo = null, ?Voucher $voucher = null): array
    {
        $quotes = [];
        foreach (config('shipping.options', []) as $option) {
            $quotes[$option['id']] = $this->calculate($items, $promo, (int) $option['price'], $voucher);
        }

        return $quotes;
    }

    /** @return array<string, int|bool> */
    public function calculate(iterable $items, ?PromoCode $promo = null, int $shipping = 350000, ?Voucher $voucher = null): array
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

        $voucherDiscount = 0;
        if ($voucher?->type === 'fixed') {
            $voucherDiscount = min((int) $voucher->discount_value, $subtotal - $discount);
            $discount += $voucherDiscount;
        }
        $shippingDiscount = $voucher?->type === 'free_shipping' ? min($shipping, (int) $voucher->discount_value) : 0;

        $net = max(0, $subtotal - $discount);
        $ppn = (int) round($subtotal * 0.11);
        $adminFee = 15000;

        return [
            'goods_subtotal' => $subtotal,
            'discount_on_goods' => $discount,
            'ppn' => $ppn,
            'shipping_estimate' => max(0, $shipping - $shippingDiscount),
            'voucher_discount_on_shipping' => $shippingDiscount,
            'voucher_discount_on_goods' => $voucherDiscount,
            'admin_fee' => $adminFee,
            'grand_total' => $net + $ppn + max(0, $shipping - $shippingDiscount) + $adminFee,
            'has_dummy_price_item' => $dummy,
            'estimated_points' => intdiv($net, 10000),
        ];
    }
}
