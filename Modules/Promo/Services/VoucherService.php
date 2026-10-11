<?php

namespace Modules\Promo\Services;

use Illuminate\Support\Str;
use Modules\Accounts\Models\Company;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Promo\Models\Voucher;

class VoucherService
{
    public function createRedemptionVoucher(LoyaltyAccount $account, int $points): Voucher
    {
        return Voucher::create([
            'code' => $this->uniqueCode('VC'),
            'title' => 'Voucher Loyalty Rp '.number_format($points * 100, 0, ',', '.'),
            'type' => 'fixed',
            'discount_value' => $points * 100,
            'min_order' => $points * 1000,
            'company_id' => $account->company_id,
            'source' => 'redeem',
            'points_cost' => $points,
            'expires_at' => now()->addDays(90),
        ]);
    }

    public function createBronzeVoucher(LoyaltyAccount $account): Voucher
    {
        return Voucher::create([
            'code' => $this->uniqueCode('BR'),
            'title' => 'Voucher Bronze Rp 25.000',
            'type' => 'fixed',
            'discount_value' => 25000,
            'min_order' => 250000,
            'company_id' => $account->company_id,
            'source' => 'tier_bronze',
            'points_cost' => null,
            'quarter_key' => now()->format('Y').'Q'.now()->quarter,
            'expires_at' => now()->addDays(90),
        ]);
    }

    public function createFreeShippingVoucher(LoyaltyAccount $account, int $slot, int $subsidy): Voucher
    {
        return Voucher::create([
            'code' => $this->uniqueCode('FS'),
            'title' => 'Voucher Gratis Ongkir',
            'type' => 'free_shipping',
            'discount_value' => $subsidy,
            'min_order' => 0,
            'company_id' => $account->company_id,
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
