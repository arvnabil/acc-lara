<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounts\Models\Company;
use Modules\Loyalty\Models\LoyaltyAccount;
class LoyaltyAccountFactory extends Factory { protected $model = LoyaltyAccount::class; public function definition(): array { return ['company_id' => Company::factory(), 'current_tier' => 'classic', 'tier_valid_until' => now()->addYear()->toDateString()]; } }
