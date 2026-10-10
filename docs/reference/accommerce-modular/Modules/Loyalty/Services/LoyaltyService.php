<?php

namespace Modules\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Loyalty\Enums\Tier;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\PointLedger;
use Modules\Loyalty\Models\Redemption;
use Modules\Orders\Models\Order;

/**
 * Satu-satunya pintu mutasi poin. Semua di DB transaction.
 */
class LoyaltyService
{
    public const POINT_VALUE_IDR = 100;
    public const MONTHLY_REDEEM_CAP = 5000;
    public const EWALLET_OPTIONS = [500, 1000, 2500];
    public const VOUCHER_OPTIONS = [750, 1500, 3000];

    public function calculateBasePoints(int $netValueIdr): int
    {
        return intdiv(max(0, $netValueIdr), 10000);
    }

    /** Idempoten lewat order.points_awarded_at + idempotency_key di ledger. */
    public function awardForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->points_awarded_at) {
                return;
            }

            $account = LoyaltyAccount::lockForUpdate()->where('company_id', $order->company_id)->firstOrFail();
            $base = $this->calculateBasePoints($order->netValueForPoints());
            $bonus = (int) floor($base * $account->current_tier->bonusRate());
            $expires = now()->addMonths(24);

            if ($base > 0) {
                $this->earn($account, 'earn_base', $base, $order->id, true, $expires, "order:{$order->id}:base");
            }
            if ($bonus > 0) {
                $this->earn($account, 'earn_bonus', $bonus, $order->id, false, $expires, "order:{$order->id}:bonus");
            }

            $account->tier_points += $base;
            $account->points_balance += $base + $bonus;
            $this->applyUpgrade($account);
            $account->save();

            $order->update(['points_awarded_at' => now()]);
        });
    }

    private function earn(LoyaltyAccount $a, string $type, int $pts, ?int $orderId, bool $tier, $expires, string $key): void
    {
        PointLedger::create([
            'loyalty_account_id' => $a->id, 'order_id' => $orderId, 'type' => $type,
            'points' => $pts, 'remaining_points' => $pts, 'counts_for_tier' => $tier,
            'earned_at' => now(), 'expires_at' => $expires, 'idempotency_key' => $key,
        ]);
    }

    /** Upgrade langsung; penurunan tidak pernah terjadi di sini. */
    private function applyUpgrade(LoyaltyAccount $a): void
    {
        $earned = Tier::forPoints($a->tier_points);
        if ($earned->rank() > $a->current_tier->rank()) {
            $a->current_tier = $earned;
            $a->tier_valid_until = now()->addYear()->endOfYear()->toDateString();
        }
    }

    /** Poin earn berusia >= 30 hari, dibatasi sisa kuota bulan berjalan. */
    public function redeemablePoints(LoyaltyAccount $a): int
    {
        $old = (int) PointLedger::where('loyalty_account_id', $a->id)
            ->whereNotNull('remaining_points')->where('remaining_points', '>', 0)
            ->where('earned_at', '<=', now()->subDays(30))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->sum('remaining_points');

        $usedThisMonth = (int) abs(PointLedger::where('loyalty_account_id', $a->id)
            ->whereIn('type', ['redeem_ewallet', 'redeem_voucher'])
            ->where('created_at', '>=', now()->startOfMonth())->sum('points'));

        return max(0, min($old, $a->points_balance, self::MONTHLY_REDEEM_CAP - $usedThisMonth));
    }

    public function redeem(LoyaltyAccount $a, int $userId, string $kind, int $points, string $idempotencyKey, array $meta = []): Redemption
    {
        $allowed = $kind === 'ewallet' ? self::EWALLET_OPTIONS : self::VOUCHER_OPTIONS;
        if (! in_array($points, $allowed, true)) {
            throw ValidationException::withMessages(['points' => 'Nominal penukaran tidak valid.']);
        }

        return DB::transaction(function () use ($a, $userId, $kind, $points, $idempotencyKey, $meta) {
            if ($existing = Redemption::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;                                   // klik ganda
            }

            $a = LoyaltyAccount::lockForUpdate()->findOrFail($a->id);
            if ($points > $this->redeemablePoints($a)) {
                throw ValidationException::withMessages(['points' => 'Poin belum dapat ditukar (usia < 30 hari atau kuota bulanan habis).']);
            }

            $this->consumeFifo($a, $points);
            $a->points_balance -= $points;                           // tier_points TIDAK berubah
            $a->save();

            PointLedger::create([
                'loyalty_account_id' => $a->id, 'type' => "redeem_{$kind}", 'points' => -$points,
                'idempotency_key' => "redeem:{$idempotencyKey}",
            ]);

            return Redemption::create([
                'loyalty_account_id' => $a->id, 'type' => $kind, 'points' => $points,
                'amount_idr' => $points * self::POINT_VALUE_IDR,     // server yang menghitung rupiah
                'requested_by' => $userId, 'idempotency_key' => $idempotencyKey,
                'ewallet_provider' => $meta['provider'] ?? null, 'ewallet_account' => $meta['account'] ?? null,
            ]);
        });
    }

    private function consumeFifo(LoyaltyAccount $a, int $points): void
    {
        $need = $points;
        $rows = PointLedger::where('loyalty_account_id', $a->id)
            ->where('remaining_points', '>', 0)->where('earned_at', '<=', now()->subDays(30))
            ->orderBy('earned_at')->orderBy('id')->lockForUpdate()->get();

        foreach ($rows as $row) {
            if ($need <= 0) {
                break;
            }
            $take = min($need, $row->remaining_points);
            $row->decrement('remaining_points', $take);
            $need -= $take;
        }
    }

    public function expirePoints(): void
    {
        PointLedger::where('remaining_points', '>', 0)->where('expires_at', '<=', now())
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::transaction(function () use ($row) {
                        $a = LoyaltyAccount::lockForUpdate()->find($row->loyalty_account_id);
                        $left = $row->remaining_points;
                        if (! $a || $left <= 0) {
                            return;
                        }
                        $row->update(['remaining_points' => 0]);
                        $a->decrement('points_balance', $left);
                        PointLedger::create(['loyalty_account_id' => $a->id, 'type' => 'expire', 'points' => -$left,
                            'idempotency_key' => "expire:{$row->id}"]);
                    });
                }
            });
    }

    /** 31 Des: turun maksimal 1 level. Harus berjalan SEBELUM reset 1 Jan. */
    public function evaluateYearEnd(): void
    {
        LoyaltyAccount::whereDate('tier_valid_until', '<=', now()->endOfYear()->toDateString())
            ->each(function (LoyaltyAccount $a) {
                $earned = Tier::forPoints($a->tier_points);
                if ($earned->rank() < $a->current_tier->rank()) {
                    $a->current_tier = $a->current_tier->oneLevelDown();
                }
                $a->tier_valid_until = now()->addYear()->endOfYear()->toDateString();
                $a->save();
            });
    }

    public function resetTierPoints(): void
    {
        LoyaltyAccount::query()->update(['tier_points' => 0]);
    }

    /** Props untuk Inertia shared data. */
    public function sharedProps(LoyaltyAccount $a, bool $isCompanyAdmin): array
    {
        $next = $a->current_tier->next();

        return [
            'current_tier' => $a->current_tier->value,
            'tier_valid_until' => $a->tier_valid_until?->toDateString(),
            'tier_points' => $a->tier_points,
            'points_balance' => $a->points_balance,
            'balance_value_idr' => $a->points_balance * self::POINT_VALUE_IDR,
            'redeemable_points' => $this->redeemablePoints($a),
            'next_tier' => $next?->value,
            'remaining_spend_idr' => $next ? max(0, $next->threshold() - $a->tier_points) * 10000 : 0,
            'can_redeem' => $isCompanyAdmin,
        ];
    }
}
