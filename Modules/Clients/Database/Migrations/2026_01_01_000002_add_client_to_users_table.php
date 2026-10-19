<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'client_id') || Schema::hasColumn('users', 'company_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->unsignedBigInteger('client_id')->nullable()->after('phone');
            $table->enum('company_role', ['admin', 'member'])->nullable()->after('client_id');

            $table->foreign('client_id', 'fk_users_client')
                ->references('id')
                ->on('clients')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_client');
            $table->dropColumn(['company_role', 'client_id', 'phone']);
        });
    }
};


