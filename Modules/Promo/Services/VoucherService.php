<?php

namespace Modules\Promo\Services;

use Illuminate\Support\Str;
use Modules\Clients\Models\Client;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Promo\Models\Voucher;

class VoucherService
{
    public function createRedemptionVoucher(LoyaltyAccount $account, int $points): Voucher
    {
        $pointValue = (int) config('loyalty.point_value_idr', 100);
        $minOrderPerPoint = (int) config('loyalty.redemption.voucher_min_order_per_point_idr', 1000);
        $validityDays = (int) config('loyalty.redemption.voucher_validity_days', 30);
        $discount = $points * $pointValue;

        return Voucher::create([
            'code' => $this->uniqueCode('VC'),
            'title' => 'Voucher Loyalty Rp '.number_format($discount, 0, ',', '.'),
            'type' => 'fixed',
            'discount_value' => $discount,
            'min_order' => $points * $minOrderPerPoint,
            'client_id' => $account->client_id,
            'source' => 'redeem',
            'points_cost' => $points,
            'expires_at' => now()->addDays($validityDays),
        ]);
    }

    public function createBronzeVoucher(LoyaltyAccount $account): Voucher
    {
        if (! in_array($account->current_tier->value, ['bronze', 'silver', 'gold', 'platinum', 'diamond'], true)) {
            throw new \InvalidArgumentException('Tier belum memenuhi syarat voucher Bronze.');
        }

        return Voucher::create([
            'code' => $this->uniqueCode('BR'),
            'title' => 'Voucher Bronze Rp 25.000',
            'type' => 'fixed',
            'discount_value' => 25000,
            'min_order' => 250000,
            'client_id' => $account->client_id,
            'source' => 'tier_bronze',
            'points_cost' => null,
            'quarter_key' => now()->format('Y').'Q'.now()->quarter,
            'expires_at' => now()->addDays(90),
        ]);
    }

    public function createFreeShippingVoucher(LoyaltyAccount $account, int $slot, int $subsidy): Voucher
    {
        if ($slot < 1 || $subsidy < 0) {
            throw new \InvalidArgumentException('Slot atau plafon voucher tidak valid.');
        }

        return Voucher::create([
            'code' => $this->uniqueCode('FS'),
            'title' => 'Voucher Gratis Ongkir',
            'type' => 'free_shipping',
            'discount_value' => $subsidy,
            'min_order' => 0,
            'client_id' => $account->client_id,
            'source' => 'free_shipping',
            'month_key' => now()->format('Y-m'),
            'slot' => $slot,
            'expires_at' => now()->endOfMonth(),
        ]);
    }

    private function uniqueCode(string $prefix): string
    {
        do {
            $code = $prefix.'-'.strtoupper(Str::random(10));
        } while (Voucher::query()->where('code', $code)->exists());

        return $code;
    }
}




