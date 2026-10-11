<?php

namespace Modules\Promo\Services;

use Modules\Promo\Models\PromoCode;

class PromoService
{
    /** @return array{promo: PromoCode, discount: int} */
    public function validate(string $code, int $goodsSubtotal): array
    {
        $promo = PromoCode::query()->where('code', strtoupper(trim($code)))->first();

        if (! $promo || ! $promo->is_active || ($promo->valid_from && $promo->valid_from->isFuture()) || ($promo->valid_until && $promo->valid_until->isPast()) || ($promo->max_uses !== null && $promo->used_count >= $promo->max_uses) || $goodsSubtotal < $promo->min_order) {
            throw new \InvalidArgumentException('❌ Kode promo tidak valid atau sudah kedaluwarsa.');
        }

        $discount = $promo->type === 'percent'
            ? (int) round($goodsSubtotal * $promo->value / 100)
            : min((int) $promo->value, $goodsSubtotal);

        return ['promo' => $promo, 'discount' => min($discount, $goodsSubtotal)];
    }
}
