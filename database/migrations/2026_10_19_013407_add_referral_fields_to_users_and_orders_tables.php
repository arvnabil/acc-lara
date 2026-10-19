<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 20)->nullable()->unique()->after('email');
            $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('referral_code', 20)->nullable()->after('voucher_id');
            $table->foreignId('referrer_user_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });
        Schema::table('carts', function (Blueprint $table): void {
            $table->string('referral_code', 20)->nullable()->after('voucher_id');
        });
        DB::table('users')->whereNull('referral_code')->orderBy('id')->eachById(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['referral_code' => 'REF'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by_user_id']);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['referrer_user_id']);
            $table->dropColumn(['referral_code', 'referrer_user_id']);
        });
        Schema::table('carts', function (Blueprint $table): void {
            $table->dropColumn('referral_code');
        });
    }
};
