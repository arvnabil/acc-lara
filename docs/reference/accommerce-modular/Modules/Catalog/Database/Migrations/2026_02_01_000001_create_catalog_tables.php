<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('full_path')->index();
            $t->boolean('is_addon')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->boolean('is_brand_group')->default(false);
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('price')->default(0);            // rupiah, integer
            $t->unsignedBigInteger('sale_price')->nullable();
            $t->boolean('in_stock')->default(true);
            $t->boolean('is_dummy_price')->default(false);          // harga estimasi
            $t->boolean('is_price_outlier')->default(false);
            $t->boolean('is_flash_sale')->default(false);
            $t->json('images')->nullable();                         // pecah dari string koma di JSON sumber
            $t->text('description')->nullable();
            $t->timestamps();
            $t->index(['category_id', 'brand_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
