<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->unique();
            $table->integer('tier_points')->default(0);
            $table->integer('points_balance')->default(0);
            $table->enum('current_tier', ['classic', 'bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('classic');
            $table->date('tier_valid_until');
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
        });

        Schema::create('point_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loyalty_account_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('review_id')->nullable();
            $table->enum('type', ['earn_base', 'earn_bonus', 'earn_review', 'redeem_ewallet', 'redeem_voucher', 'expire', 'reversal', 'adjustment']);
            $table->integer('points');
            $table->integer('remaining_points')->nullable();
            $table->boolean('counts_for_tier')->default(false);
            $table->timestamp('earned_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('notes', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('loyalty_account_id', 'idx_loyalty_account_id');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('order_id', 'idx_order_id');
            $table->foreign('loyalty_account_id')->references('id')->on('loyalty_accounts')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
        });

        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loyalty_account_id');
            $table->enum('type', ['ewallet', 'voucher']);
            $table->unsignedInteger('points');
            $table->unsignedBigInteger('amount_idr');
            $table->string('ewallet_provider', 50)->nullable();
            $table->string('ewallet_account', 100)->nullable();
            $table->string('ewallet_account_name')->nullable();
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->timestamps();
            $table->index('loyalty_account_id', 'idx_loyalty_account_id');
            $table->index(['loyalty_account_id', 'created_at'], 'idx_loyalty_account_created');
            $table->foreign('loyalty_account_id')->references('id')->on('loyalty_accounts')->restrictOnDelete();
            $table->foreign('voucher_id')->references('id')->on('vouchers')->restrictOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('point_ledger');
        Schema::dropIfExists('loyalty_accounts');
    }
};


