<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\PointLedger;
class PointLedgerFactory extends Factory { protected $model = PointLedger::class; public function definition(): array { return ['loyalty_account_id' => LoyaltyAccount::factory(), 'type' => 'earn_base', 'points' => 100, 'earned_at' => now(), 'created_at' => now(), 'idempotency_key' => fake()->unique()->bothify('LEDGER-#####')]; } }
