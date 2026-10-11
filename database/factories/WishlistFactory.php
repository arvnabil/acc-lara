<?php
namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Cart\Models\Wishlist;
use Modules\Catalog\Models\Product;
class WishlistFactory extends Factory { protected $model = Wishlist::class; public function definition(): array { return ['user_id' => User::factory(), 'product_id' => Product::factory(), 'created_at' => now()]; } }
