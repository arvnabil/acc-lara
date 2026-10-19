<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('provider', 20)->default('midtrans');
            $table->string('provider_order_id', 50)->unique();
            $table->unsignedTinyInteger('attempt');
            $table->string('snap_token', 100)->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->string('status', 30);
            $table->string('payment_channel', 50)->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->index('order_id', 'idx_payment_transactions_order_id');
            $table->index(['status', 'last_checked_at'], 'idx_payment_transactions_status_checked');
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
