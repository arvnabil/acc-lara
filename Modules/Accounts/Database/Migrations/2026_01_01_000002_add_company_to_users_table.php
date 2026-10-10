<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->unsignedBigInteger('company_id')->nullable()->after('phone');
            $table->enum('company_role', ['admin', 'member'])->nullable()->after('company_id');

            $table->foreign('company_id', 'fk_users_company')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_company');
            $table->dropColumn(['company_role', 'company_id', 'phone']);
        });
    }
};
