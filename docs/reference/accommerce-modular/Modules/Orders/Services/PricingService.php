<?php

namespace Modules\Orders\Services;

/**
 * SATU sumber perhitungan harga untuk Cart dan Checkout (menggantikan
 * SHIPPING_BASE di Cart dan SHIPPING_OPTIONS di Checkout pada kode React).
 * Klien hanya menampilkan hasilnya.
 */
class PricingService
{
    public const ADMIN_FEE = 15000;
    public const PPN_RATE = 0.11;

    /** @param array<int, array{unit_price:int, qty:int}> $lines */
    public function quote(array $lines, int $shippingCost, int $discountOnGoods = 0): array
    {
        $subtotal = (int) array_sum(array_map(fn ($l) => $l['unit_price'] * $l['qty'], $lines));
        $netGoods = max(0, $subtotal - $discountOnGoods);
        $ppn = (int) round($subtotal * self::PPN_RATE);          // TODO: konfirmasi akuntan (sebelum/sesudah diskon)
        $grand = max(0, $subtotal + $ppn + $shippingCost + self::ADMIN_FEE - $discountOnGoods);

        return [
            'goods_subtotal' => $subtotal,
            'discount_on_goods' => $discountOnGoods,
            'ppn' => $ppn,
            'shipping_cost' => $shippingCost,
            'admin_fee' => self::ADMIN_FEE,
            'grand_total' => $grand,
            'estimated_base_points' => intdiv($netGoods, 10000),   // hanya estimasi, final dihitung LoyaltyService
        ];
    }
}
