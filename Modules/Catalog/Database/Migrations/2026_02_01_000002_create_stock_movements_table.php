<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('variants')->restrictOnDelete();
            $t->integer('delta'); // Negatif = berkurang, Positif = bertambah
            $t->enum('reason', ['order', 'cancel', 'expired', 'returned', 'adjustment', 'seeder']);
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('note')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
