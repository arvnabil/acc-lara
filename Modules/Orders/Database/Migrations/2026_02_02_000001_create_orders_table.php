<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();                         // ACC-xxxxx berurutan dari server
            $t->foreignId('user_id')->constrained();
            $t->foreignId('company_id')->nullable();
            $t->string('status')->default('pending_payment')->index();
            $t->string('payment_type')->default('direct');          // direct | net
            $t->unsignedTinyInteger('net_days')->nullable();        // 14 / 30
            $t->unsignedBigInteger('goods_subtotal');
            $t->unsignedBigInteger('discount_on_goods')->default(0);
            $t->unsignedBigInteger('ppn')->default(0);
            $t->unsignedBigInteger('shipping_cost')->default(0);
            $t->unsignedBigInteger('admin_fee')->default(0);
            $t->unsignedBigInteger('grand_total');
            $t->boolean('has_dummy_price_item')->default(false);
            $t->timestamp('completed_at')->nullable();
            $t->timestamp('invoice_paid_at')->nullable();           // Lunas (untuk NET)
            $t->timestamp('points_awarded_at')->nullable();
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->string('name');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedInteger('qty');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
