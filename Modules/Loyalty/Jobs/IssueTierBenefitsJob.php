<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Promo\Services\VoucherService;

class IssueTierBenefitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VoucherService $vouchers): void
    {
        $now = now();
        $quarter = $now->format('Y').'Q'.$now->quarter;
        $month = $now->format('Y-m');
        $slots = ['classic' => 1, 'bronze' => 1, 'silver' => 2, 'gold' => 4, 'platinum' => 4, 'diamond' => 4];

        LoyaltyAccount::query()->with('client')->each(function (LoyaltyAccount $account) use ($vouchers, $now, $quarter, $month, $slots): void {
            $tier = $account->current_tier->value;
            if (in_array($tier, ['bronze', 'silver', 'gold', 'platinum', 'diamond'], true) && in_array($now->month, [1, 4, 7, 10], true) && ! $account->client->vouchers()->where('source', 'tier_bronze')->where('quarter_key', $quarter)->exists()) {
                $vouchers->createBronzeVoucher($account);
            }
            $subsidy = config("loyalty.shipping_subsidy_idr.{$tier}");
            $count = $slots[$tier] ?? 0;
            if ($subsidy === null || $count === 0) {
                return;
            }
            for ($slot = 1; $slot <= $count; $slot++) {
                if (! $account->client->vouchers()->where('source', 'free_shipping')->where('month_key', $month)->where('slot', $slot)->exists()) {
                    $vouchers->createFreeShippingVoucher($account, $slot, (int) $subsidy);
                }
            }
        });
    }
}


