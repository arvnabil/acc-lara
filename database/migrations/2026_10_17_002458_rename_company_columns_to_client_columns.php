<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'addresses', 'orders', 'vouchers', 'loyalty_accounts', 'reviews'] as $tableName) {
            if (Schema::hasColumn($tableName, 'company_id') && ! Schema::hasColumn($tableName, 'client_id')) {
                Schema::table($tableName, fn ($table) => $table->renameColumn('company_id', 'client_id'));
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'addresses', 'orders', 'vouchers', 'loyalty_accounts', 'reviews'] as $tableName) {
            if (Schema::hasColumn($tableName, 'client_id') && ! Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, fn ($table) => $table->renameColumn('client_id', 'company_id'));
            }
        }
    }
};
