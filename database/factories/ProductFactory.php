<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;
    public function definition(): array { $name = fake()->unique()->words(3, true); return ['sku' => fake()->unique()->bothify('SKU-####'), 'name' => $name, 'slug' => fake()->unique()->slug(), 'type' => 'simple', 'product_type' => 'regular', 'regular_price' => fake()->numberBetween(10000, 1000000), 'stock' => fake()->numberBetween(0, 100), 'description' => fake()->optional()->paragraph()]; }
}
