<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Promo\Models\PromoCode;
class PromoCodeFactory extends Factory { protected $model = PromoCode::class; public function definition(): array { return ['code' => fake()->unique()->bothify('TEST##'), 'label' => fake()->sentence(3), 'type' => fake()->randomElement(['percent', 'fixed']), 'value' => fake()->numberBetween(1, 100000), 'min_order' => 0, 'is_active' => true]; } }
