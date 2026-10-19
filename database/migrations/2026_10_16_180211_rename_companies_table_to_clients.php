<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies') && ! Schema::hasTable('clients')) {
            Schema::rename('companies', 'clients');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clients') && ! Schema::hasTable('companies')) {
            Schema::rename('clients', 'companies');
        }
    }
};
