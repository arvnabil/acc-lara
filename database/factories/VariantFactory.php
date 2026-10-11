<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Variant;

class VariantFactory extends Factory
{
    protected $model = Variant::class;
    public function definition(): array { return ['product_id' => Product::factory(), 'sku' => fake()->unique()->bothify('VAR-####'), 'name' => fake()->words(2, true), 'regular_price' => fake()->numberBetween(10000, 1000000), 'stock' => fake()->numberBetween(0, 100)]; }
}
