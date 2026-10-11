<?php

namespace Modules\Loyalty\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Models\PointLedger;

class ExpirePointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(): void
    {
        PointLedger::query()->whereIn('type', ['earn_base', 'earn_bonus', 'earn_review', 'adjustment'])->where('remaining_points', '>', 0)->whereNotNull('expires_at')->where('expires_at', '<=', now())->chunkById(100, function ($ledgers): void {
            DB::transaction(function () use ($ledgers): void {
                foreach ($ledgers as $ledger) {
                    $locked = PointLedger::query()->lockForUpdate()->find($ledger->id);
                    if (! $locked || $locked->remaining_points <= 0) {
                        continue;
                    }
                    $account = $locked->loyaltyAccount()->lockForUpdate()->first();
                    $points = $locked->remaining_points;
                    $locked->update(['remaining_points' => 0]);
                    if ($account) {
                        $account->pointLedger()->create(['type' => 'expire', 'points' => -$points, 'counts_for_tier' => false, 'earned_at' => now(), 'created_at' => now(), 'idempotency_key' => "expire:{$locked->id}", 'notes' => "Expired ledger {$locked->id}"]);
                        $account->decrement('points_balance', $points);
                    }
                }
            });
        });
    }
}
