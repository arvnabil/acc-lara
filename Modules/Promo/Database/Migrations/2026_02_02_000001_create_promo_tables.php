<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label');
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('min_order')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('title');
            $table->enum('type', ['fixed', 'percent', 'free_shipping']);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('min_order')->default(0);
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->enum('source', ['tier_bronze', 'redeem', 'free_shipping']);
            $table->unsignedInteger('points_cost')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('quarter_key', 7)->nullable();
            $table->string('month_key', 7)->nullable();
            $table->unsignedTinyInteger('slot')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'source', 'quarter_key'], 'uq_bronze_per_quarter');
            $table->unique(['client_id', 'source', 'month_key', 'slot'], 'uq_free_shipping_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('promo_codes');
    }
};


