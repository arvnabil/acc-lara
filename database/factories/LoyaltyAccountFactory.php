<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clients\Models\Client;
use Modules\Loyalty\Models\LoyaltyAccount;
class LoyaltyAccountFactory extends Factory { protected $model = LoyaltyAccount::class; public function definition(): array { return ['client_id' => Client::factory(), 'current_tier' => 'classic', 'tier_valid_until' => now()->addYear()->toDateString()]; } }




