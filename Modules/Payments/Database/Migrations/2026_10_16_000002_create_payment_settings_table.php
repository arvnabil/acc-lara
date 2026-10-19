<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('midtrans_enabled')->default(true);
            $table->boolean('qris_enabled')->default(true);
            $table->boolean('bank_transfer_enabled')->default(true);
            $table->string('qris_image')->nullable();
            $table->string('qris_nmid', 50)->nullable();
            $table->string('merchant_name')->nullable();
            $table->json('bank_accounts')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
