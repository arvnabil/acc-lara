<?php
namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\Redemption;
class RedemptionFactory extends Factory { protected $model = Redemption::class; public function definition(): array { return ['loyalty_account_id' => LoyaltyAccount::factory(), 'type' => 'ewallet', 'points' => 100, 'amount_idr' => 10000, 'status' => 'pending', 'requested_by' => User::factory(), 'idempotency_key' => fake()->unique()->bothify('REDEEM-#####')]; } }
