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
        Schema::table('point_ledger', function (Blueprint $table): void {
            $table->timestamp('available_at')->nullable()->after('earned_at');
            $table->index('available_at', 'idx_point_ledger_available_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_ledger', function (Blueprint $table): void {
            $table->dropIndex('idx_point_ledger_available_at');
            $table->dropColumn('available_at');
        });
    }
};
