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
        Schema::create('shipping_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);
            $table->unsignedBigInteger('external_id');
            $table->unsignedBigInteger('parent_external_id')->nullable();
            $table->string('name');
            $table->string('postal_code', 10)->nullable();
            $table->timestamps();
            $table->unique(['type', 'external_id']);
            $table->index(['type', 'parent_external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_locations');
    }
};
