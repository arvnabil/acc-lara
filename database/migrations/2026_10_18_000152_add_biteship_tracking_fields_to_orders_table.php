<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('biteship_order_id')->nullable()->after('shipping_estimate');
            $table->string('biteship_tracking_id')->nullable()->after('biteship_order_id');
            $table->string('biteship_waybill_id')->nullable()->after('biteship_tracking_id');
            $table->string('biteship_courier_code', 50)->nullable()->after('biteship_waybill_id');
            $table->string('biteship_courier_service_code', 100)->nullable()->after('biteship_courier_code');
            $table->string('biteship_status', 50)->nullable()->after('biteship_courier_service_code');
            $table->string('biteship_tracking_url')->nullable()->after('biteship_status');
            $table->json('biteship_tracking_history')->nullable()->after('biteship_tracking_url');
            $table->timestamp('biteship_last_synced_at')->nullable()->after('biteship_tracking_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'biteship_order_id', 'biteship_tracking_id', 'biteship_waybill_id',
                'biteship_courier_code', 'biteship_courier_service_code', 'biteship_status',
                'biteship_tracking_url', 'biteship_tracking_history', 'biteship_last_synced_at',
            ]);
        });
    }
};
