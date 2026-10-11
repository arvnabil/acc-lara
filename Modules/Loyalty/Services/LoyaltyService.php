<?php

namespace Modules\Loyalty\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Enums\LoyaltyTier;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\PointLedger;
use Modules\Orders\Models\Order;
use Modules\Promo\Services\VoucherService;

class LoyaltyService
{
    public function createAccount(int $companyId): LoyaltyAccount
    {
        return LoyaltyAccount::query()->firstOrCreate(
            ['company_id' => $companyId],
            ['current_tier' => LoyaltyTier::Classic, 'tier_valid_until' => now()->addYear()->endOfYear()->toDateString()],
        );
    }

    public function accountForUser(User $user): ?LoyaltyAccount
    {
        return $user->company_id ? $this->createAccount((int) $user->company_id) : null;
    }

    public function calculateBasePoints(int $netValueIdr): int
    {
        return intdiv(max(0, $netValueIdr), (int) config('loyalty.spend_per_point_idr', 10000));
    }

    public function awardForOrder(Order $order): ?LoyaltyAccount
    {
        return DB::transaction(function () use ($order): ?LoyaltyAccount {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->points_awarded_at || $order->points_skipped_reason) {
                return $order->company_id ? $this->createAccount((int) $order->company_id) : null;
            }

            $account = LoyaltyAccount::query()->where('company_id', $order->company_id)->lockForUpdate()->firstOrFail();
            $netValue = max(0, (int) $order->goods_subtotal - (int) $order->discount_on_goods);
            $basePoints = $this->calculateBasePoints($netValue);

            if ($order->has_dummy_price_item) {
                $order->update(['points_skipped_reason' => 'dummy_price']);

                return $account;
            }

            if ($basePoints === 0) {
                $order->update(['points_skipped_reason' => 'zero_points']);

                return $account;
            }

            $expiresAt = now()->addMonths((int) config('loyalty.redemption.expiry_months', 24));
            $base = PointLedger::query()->firstOrCreate(
                ['idempotency_key' => "earn_base:{$order->id}"],
                ['loyalty_account_id' => $account->id, 'order_id' => $order->id, 'type' => 'earn_base', 'points' => $basePoints, 'remaining_points' => $basePoints, 'counts_for_tier' => true, 'earned_at' => now(), 'expires_at' => $expiresAt, 'created_at' => now()],
            );

            if ($base->wasRecentlyCreated) {
                $account->increment('points_balance', $basePoints);
                $account->increment('tier_points', $basePoints);
            }

            $bonusPoints = intdiv($basePoints * $this->bonusPercent($account->current_tier), 100);
            if ($bonusPoints > 0) {
                $bonus = PointLedger::query()->firstOrCreate(
                    ['idempotency_key' => "earn_bonus:{$order->id}"],
                    ['loyalty_account_id' => $account->id, 'order_id' => $order->id, 'type' => 'earn_bonus', 'points' => $bonusPoints, 'remaining_points' => $bonusPoints, 'counts_for_tier' => false, 'earned_at' => now(), 'expires_at' => $expiresAt, 'created_at' => now()],
                );
                if ($bonus->wasRecentlyCreated) {
                    $account->increment('points_balance', $bonusPoints);
                }
            }

            $order->update(['points_awarded_at' => now()]);
            $account->refresh();
            $this->checkAndUpgradeTier($account);

            return $account->fresh();
        });
    }

    public function reverseForOrder(Order $order): ?LoyaltyAccount
    {
        return DB::transaction(function () use ($order): ?LoyaltyAccount {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->points_awarded_at || ! $order->company_id) {
                return null;
            }

            $account = LoyaltyAccount::query()->where('company_id', $order->company_id)->lockForUpdate()->firstOrFail();
            $earnings = PointLedger::query()->where('loyalty_account_id', $account->id)->where('order_id', $order->id)->whereIn('type', ['earn_base', 'earn_bonus', 'earn_review'])->lockForUpdate()->get();
            $basePoints = (int) $earnings->where('type', 'earn_base')->sum('points');
            $allPoints = (int) $earnings->sum('points');

            foreach ($earnings as $earning) {
                $earning->update(['remaining_points' => 0]);
            }

            if ($basePoints > 0) {
                PointLedger::query()->firstOrCreate(['idempotency_key' => "reversal_base:{$order->id}"], ['loyalty_account_id' => $account->id, 'order_id' => $order->id, 'type' => 'reversal', 'points' => -$basePoints, 'counts_for_tier' => true, 'earned_at' => now(), 'created_at' => now(), 'notes' => 'Order returned']);
            }
            if ($allPoints - $basePoints > 0) {
                PointLedger::query()->firstOrCreate(['idempotency_key' => "reversal_bonus:{$order->id}"], ['loyalty_account_id' => $account->id, 'order_id' => $order->id, 'type' => 'reversal', 'points' => -($allPoints - $basePoints), 'counts_for_tier' => false, 'earned_at' => now(), 'created_at' => now(), 'notes' => 'Order returned']);
            }

            $account->update(['tier_points' => max(0, $account->tier_points - $basePoints), 'points_balance' => $account->points_balance - $allPoints]);

            return $account->fresh();
        });
    }

    public function evaluateYearEnd(?\DateTimeInterface $at = null): void
    {
        $date = $at ? now()->setDate((int) $at->format('Y'), (int) $at->format('m'), (int) $at->format('d')) : now();

        LoyaltyAccount::query()->whereDate('tier_valid_until', $date->toDateString())->each(function (LoyaltyAccount $account): void {
            DB::transaction(function () use ($account): void {
                $account = LoyaltyAccount::query()->lockForUpdate()->findOrFail($account->id);
                $tiers = array_keys(config('loyalty.tiers'));
                $currentIndex = array_search($account->current_tier->value, $tiers, true);
                $earnedIndex = 0;
                foreach (config('loyalty.tiers') as $key => $tier) {
                    if ($account->tier_points >= $tier['min']) {
                        $earnedIndex = array_search($key, $tiers, true);
                    }
                }
                if ($earnedIndex < $currentIndex) {
                    $account->current_tier = LoyaltyTier::from($tiers[max(0, $currentIndex - 1)]);
                }
                $account->tier_valid_until = now()->addYear()->endOfYear()->toDateString();
                $account->save();
            });
        });
    }

    public function resetTierPoints(): void
    {
        LoyaltyAccount::query()->update(['tier_points' => 0]);
    }

    public function redeemablePoints(LoyaltyAccount $account, bool $includeMonthlyLimit = true): int
    {
        $matured = PointLedger::query()->where('loyalty_account_id', $account->id)->whereIn('type', ['earn_base', 'earn_bonus', 'earn_review', 'adjustment'])->where('remaining_points', '>', 0)->where('earned_at', '<=', now()->subDays((int) config('loyalty.redemption.maturity_days', 30)))->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->sum('remaining_points');
        if (! $includeMonthlyLimit) {
            return max(0, (int) $matured);
        }

        $monthlyEwallet = PointLedger::query()->where('loyalty_account_id', $account->id)->where('type', 'redeem_ewallet')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum(DB::raw('ABS(points)'));

        return max(0, min((int) $matured, (int) config('loyalty.redemption.ewallet_monthly_limit', 5000) - (int) $monthlyEwallet));
    }

    /** @param array<string, mixed> $data */
    public function redeem(User $user, array $data): array
    {
        abort_unless($user->company_role === 'admin', 403);

        return DB::transaction(function () use ($user, $data): array {
            $account = LoyaltyAccount::query()->where('company_id', $user->company_id)->lockForUpdate()->firstOrFail();
            $existing = $account->redemptions()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return ['redemption' => $existing->load('voucher'), 'account' => $account->fresh()];
            }

            $type = $data['type'];
            $points = (int) $data['points'];
            $allowed = $type === 'ewallet' ? config('loyalty.redemption.ewallet_points') : config('loyalty.redemption.voucher_points');
            abort_unless(in_array($points, $allowed, true), 422, 'Nominal penukaran tidak tersedia.');
            abort_if($account->points_balance < $points, 422, 'Saldo poin tidak mencukupi.');
            abort_if($this->redeemablePoints($account, $type === 'ewallet') < $points, 422, 'Poin belum matang atau kuota bulan ini tidak mencukupi.');

            $remaining = $points;
            $matured = PointLedger::query()->where('loyalty_account_id', $account->id)->whereIn('type', ['earn_base', 'earn_bonus', 'earn_review', 'adjustment'])->where('remaining_points', '>', 0)->where('earned_at', '<=', now()->subDays((int) config('loyalty.redemption.maturity_days', 30)))->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderBy('earned_at')->lockForUpdate()->get();
            foreach ($matured as $ledger) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, (int) $ledger->remaining_points);
                $ledger->decrement('remaining_points', $take);
                $remaining -= $take;
            }
            abort_if($remaining > 0, 422, 'Poin matang tidak mencukupi.');

            $voucher = $type === 'voucher' ? app(VoucherService::class)->createRedemptionVoucher($account, $points) : null;
            $redemption = $account->redemptions()->create([
                'type' => $type,
                'points' => $points,
                'amount_idr' => $points * (int) config('loyalty.point_value_idr', 100),
                'ewallet_provider' => $data['provider'] ?? null,
                'ewallet_account' => $data['account'] ?? null,
                'ewallet_account_name' => $data['account_name'] ?? null,
                'voucher_id' => $voucher?->id,
                'status' => $type === 'voucher' ? 'paid' : 'pending',
                'requested_by' => $user->id,
                'idempotency_key' => $data['idempotency_key'],
            ]);
            $account->decrement('points_balance', $points);
            $account->pointLedger()->create(['type' => $type === 'ewallet' ? 'redeem_ewallet' : 'redeem_voucher', 'points' => -$points, 'counts_for_tier' => false, 'earned_at' => now(), 'created_at' => now(), 'idempotency_key' => "{$type}:{$data['idempotency_key']}"]);

            return ['redemption' => $redemption->load('voucher'), 'account' => $account->fresh()];
        });
    }

    public function refundRedemption(\Modules\Loyalty\Models\Redemption $redemption, string $reason): void
    {
        DB::transaction(function () use ($redemption, $reason): void {
            $redemption = $redemption->newQuery()->lockForUpdate()->findOrFail($redemption->id);
            if (in_array($redemption->status, ['failed', 'cancelled'], true)) {
                return;
            }
            $account = $redemption->loyaltyAccount()->lockForUpdate()->firstOrFail();
            $key = "adjustment:{$redemption->id}";
            if (! $account->pointLedger()->where('idempotency_key', $key)->exists()) {
                $account->pointLedger()->create(['type' => 'adjustment', 'points' => $redemption->points, 'remaining_points' => $redemption->points, 'counts_for_tier' => false, 'earned_at' => now(), 'expires_at' => now()->addMonths((int) config('loyalty.redemption.expiry_months', 24)), 'idempotency_key' => $key, 'notes' => $reason]);
                $account->increment('points_balance', $redemption->points);
            }
            $redemption->update(['status' => 'failed', 'rejection_reason' => $reason, 'processed_at' => now()]);
        });
    }

    public function sharedProps(?User $user): ?array
    {
        $account = $user ? $this->accountForUser($user) : null;
        if (! $account) {
            return null;
        }

        $tier = $account->current_tier->value;
        $tiers = config('loyalty.tiers');
        $next = collect($tiers)->keys()->skipWhile(fn (string $key): bool => $key !== $tier)->skip(1)->first();

        return [
            'current_tier' => $tier,
            'tier_valid_until' => $account->tier_valid_until?->toDateString(),
            'tier_points' => $account->tier_points,
            'points_balance' => $account->points_balance,
            'balance_value_idr' => $account->points_balance * (int) config('loyalty.point_value_idr', 100),
            'redeemable_points' => $this->redeemablePoints($account),
            'next_tier' => $next,
            'remaining_spend_idr' => $next ? max(0, ($tiers[$next]['min'] - $account->tier_points) * (int) config('loyalty.spend_per_point_idr', 10000)) : 0,
            'can_redeem' => $user->company_role === 'admin',
            'ewallet_paid_total_idr' => $account->redemptions()->where('type', 'ewallet')->where('status', 'paid')->sum('amount_idr'),
        ];
    }

    private function bonusPercent(LoyaltyTier $tier): int
    {
        return (int) config("loyalty.tiers.{$tier->value}.bonus_percent", 0);
    }

    private function checkAndUpgradeTier(LoyaltyAccount $account): void
    {
        $currentIndex = array_search($account->current_tier->value, array_keys(config('loyalty.tiers')), true);
        $candidate = $account->current_tier;
        foreach (config('loyalty.tiers') as $key => $tier) {
            if ($account->tier_points >= $tier['min'] && array_search($key, array_keys(config('loyalty.tiers')), true) > $currentIndex) {
                $candidate = LoyaltyTier::from($key);
            }
        }
        if ($candidate !== $account->current_tier) {
            $account->update(['current_tier' => $candidate, 'tier_valid_until' => now()->addYear()->endOfYear()->toDateString()]);
        }
    }
}
