<?php

namespace Modules\Promo\Services;

use Modules\Promo\Models\PromoCode;
use Modules\Clients\Models\Client;
use Modules\Promo\Models\Voucher;
use App\Models\User;

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
            ? min((int) round($goodsSubtotal * $promo->value / 100), $promo->max_discount ?? PHP_INT_MAX)
            : min((int) $promo->value, $goodsSubtotal);

        return ['promo' => $promo, 'discount' => min($discount, $goodsSubtotal)];
    }

    /** @return array{promo: PromoCode|null, voucher: Voucher|null, discount: int} */
    public function validateForClient(string $code, int $goodsSubtotal, ?Client $client, ?User $user = null): array
    {
        try {
            $result = [...$this->validate($code, $goodsSubtotal), 'voucher' => null];
            $promo = $result['promo'];

            if ($user && $promo->max_uses_per_user !== null) {
                $usedByUser = $promo->orders()
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['cancelled', 'returned'])
                    ->count();

                if ($usedByUser >= $promo->max_uses_per_user) {
                    throw new \InvalidArgumentException('❌ Batas penggunaan promo untuk akun Anda sudah tercapai.');
                }
            }

            return $result;
        } catch (\InvalidArgumentException $exception) {
            if (str_contains($exception->getMessage(), 'Batas penggunaan promo')) {
                throw $exception;
            }

            $voucher = Voucher::query()->where('code', strtoupper(trim($code)))
                ->where('client_id', $client?->id)
                ->whereNull('used_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->first();
            if (! $voucher || $goodsSubtotal < $voucher->min_order) {
                throw new \InvalidArgumentException('❌ Kode promo tidak valid atau sudah kedaluwarsa.');
            }

            $tier = $client?->loyaltyAccount?->current_tier?->value;
            $eligibleTiers = ['bronze', 'silver', 'gold', 'platinum', 'diamond'];
            if ($voucher->source === 'tier_bronze' && ! in_array($tier, $eligibleTiers, true)) {
                throw new \InvalidArgumentException('❌ Kode promo tidak valid atau sudah kedaluwarsa.');
            }

            $discount = $voucher->type === 'percent' ? (int) round($goodsSubtotal * $voucher->discount_value / 100) : ($voucher->type === 'fixed' ? min($voucher->discount_value, $goodsSubtotal) : 0);

            return ['promo' => null, 'voucher' => $voucher, 'discount' => $discount];
        }
    }
}




