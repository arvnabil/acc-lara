<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
            $table->enum('status', ['pending_payment', 'awaiting_verification', 'paid', 'processing', 'packed', 'shipped', 'completed', 'returned', 'cancelled'])->default('pending_payment');
            $table->string('payment_type', 20)->default('direct');
            $table->unsignedTinyInteger('net_days')->nullable();
            $table->unsignedBigInteger('goods_subtotal');
            $table->unsignedBigInteger('discount_on_goods')->default(0);
            $table->unsignedBigInteger('ppn')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('admin_fee')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->boolean('has_dummy_price_item')->default(false);
            $table->unsignedBigInteger('promo_code_id')->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->string('shipping_label')->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->json('shipping_address');
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('invoice_paid_at')->nullable();
            $table->timestamp('points_awarded_at')->nullable();
            $table->string('points_skipped_reason', 30)->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index('status', 'idx_status');
            $table->index('user_id', 'idx_user_id');
            $table->index('client_id', 'idx_client_id');
            $table->index('points_awarded_at', 'idx_points_awarded_at');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->restrictOnDelete();
            $table->foreign('voucher_id')->references('id')->on('vouchers')->restrictOnDelete();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
            $table->index('order_id', 'idx_order_id');
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name', 500);
            $table->string('variant_name')->nullable();
            $table->string('sku', 100)->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->integer('quantity');
            $table->unsignedBigInteger('subtotal');
            $table->boolean('is_dummy_price')->default(false);
            $table->date('rental_start')->nullable();
            $table->date('rental_end')->nullable();
            $table->integer('rental_days')->nullable();
            $table->timestamps();
            $table->index('order_id', 'idx_order_id');
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->foreign('variant_id')->references('id')->on('variants')->restrictOnDelete();
        });

        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('file_path', 500);
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->index('order_id', 'idx_order_id');
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('orders');
    }
};


