<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounts\Models\Company;
use Modules\Promo\Models\Voucher;
class VoucherFactory extends Factory { protected $model = Voucher::class; public function definition(): array { return ['code' => fake()->unique()->bothify('VCH-#####'), 'title' => fake()->sentence(3), 'type' => 'fixed', 'discount_value' => fake()->numberBetween(1000, 100000), 'company_id' => Company::factory(), 'source' => 'redeem']; } }
