<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('npwp')->nullable()->unique();     // null = perorangan
            $t->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $t->integer('tier_points')->default(0);        // reset 1 Januari
            $t->integer('points_balance')->default(0);     // boleh minus
            $t->string('current_tier')->default('classic');
            $t->date('tier_valid_until')->nullable();
            $t->timestamps();
        });

        Schema::create('point_ledger', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->nullable();
            $t->foreignId('review_id')->nullable();
            $t->string('type');                             // earn_base, earn_bonus, earn_review, redeem_*, expire, reversal, adjustment
            $t->integer('points');
            $t->integer('remaining_points')->nullable();    // hanya baris earn, untuk FIFO
            $t->boolean('counts_for_tier')->default(false);
            $t->timestamp('earned_at')->useCurrent();
            $t->timestamp('expires_at')->nullable()->index();
            $t->string('idempotency_key')->nullable()->unique();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $t->string('type');                             // ewallet | voucher
            $t->unsignedInteger('points');
            $t->unsignedBigInteger('amount_idr');
            $t->string('ewallet_provider')->nullable();
            $t->string('ewallet_account')->nullable();
            $t->string('voucher_code')->nullable();
            $t->string('status')->default('pending');
            $t->foreignId('requested_by')->constrained('users');
            $t->string('idempotency_key')->unique();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('point_ledger');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('companies');
    }
};
