<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Content\Models\Banner;
class BannerFactory extends Factory { protected $model = Banner::class; public function definition(): array { return ['title' => fake()->sentence(4), 'image_url' => fake()->imageUrl(), 'sort_order' => 0, 'is_active' => true]; } }
