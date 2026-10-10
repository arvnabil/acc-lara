<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('logo_url', 500)->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('full_path', 500)->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $t->boolean('is_addon')->default(false);
            $t->boolean('is_brand_group')->default(false);
            $t->integer('product_count')->default(0);
            $t->integer('sort_order')->default(0);
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('sku', 100)->nullable();
            $t->string('name', 500);
            $t->string('slug', 500)->unique();
            $t->enum('type', ['simple', 'variable'])->default('simple');
            $t->enum('product_type', ['regular', 'rental', 'license'])->default('regular');
            $t->unsignedBigInteger('regular_price')->default(0);
            $t->unsignedBigInteger('sale_price')->nullable();
            $t->boolean('is_dummy_price')->default(false);
            $t->boolean('is_price_outlier')->default(false);
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_flash_sale')->default(false);
            $t->boolean('is_addon')->default(false);
            $t->integer('stock')->default(0);
            $t->foreignId('brand_id')->nullable()->constrained('brands')->restrictOnDelete();
            $t->integer('sort_order')->default(0);
            $t->longText('description')->nullable();
            $t->text('short_description')->nullable();
            $t->string('source_id', 50)->nullable();
            $t->softDeletes();
            $t->timestamps();

            $t->index('slug');
            $t->index('sort_order');
            $t->index('brand_id');
            $t->index('product_type');
        });

        Schema::create('category_product', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->primary(['category_id', 'product_id']);
        });

        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->string('url', 500);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $t->string('source_id', 50)->nullable();
            $t->string('sku', 100)->nullable();
            $t->string('name', 500);
            $t->unsignedBigInteger('regular_price')->default(0);
            $t->unsignedBigInteger('sale_price')->nullable();
            $t->boolean('is_dummy_price')->default(false);
            $t->boolean('is_price_outlier')->default(false);
            $t->integer('stock')->default(0);
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('variant_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
            $t->string('url', 500);
            $t->integer('sort_order')->default(0);
        });

        Schema::create('product_attributes', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('value', 500);
            $t->morphs('attributable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('variant_images');
        Schema::dropIfExists('variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
