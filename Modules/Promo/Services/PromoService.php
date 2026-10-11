<?php

namespace Modules\Promo\Services;

use Modules\Promo\Models\PromoCode;
use Modules\Accounts\Models\Company;
use Modules\Promo\Models\Voucher;

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

    /** @return array{promo: PromoCode|null, voucher: Voucher|null, discount: int} */
    public function validateForCompany(string $code, int $goodsSubtotal, ?Company $company): array
    {
        try {
            return [...$this->validate($code, $goodsSubtotal), 'voucher' => null];
        } catch (\InvalidArgumentException) {
            $voucher = Voucher::query()->where('code', strtoupper(trim($code)))
                ->where('company_id', $company?->id)
                ->whereNull('used_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->first();
            if (! $voucher || $goodsSubtotal < $voucher->min_order) {
                throw new \InvalidArgumentException('❌ Kode promo tidak valid atau sudah kedaluwarsa.');
            }

            if ($voucher->source === 'tier_bronze' && $company?->loyaltyAccount?->current_tier?->value !== 'bronze') {
                throw new \InvalidArgumentException('❌ Kode promo tidak valid atau sudah kedaluwarsa.');
            }

            $discount = $voucher->type === 'percent' ? (int) round($goodsSubtotal * $voucher->discount_value / 100) : ($voucher->type === 'fixed' ? min($voucher->discount_value, $goodsSubtotal) : 0);

            return ['promo' => null, 'voucher' => $voucher, 'discount' => $discount];
        }
    }
}
