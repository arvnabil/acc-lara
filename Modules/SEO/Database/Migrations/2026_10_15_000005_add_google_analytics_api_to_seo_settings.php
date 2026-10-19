<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_settings', function (Blueprint $table): void {
            $table->string('ga4_property_id')->nullable()->after('search_console_verification');
            $table->text('ga4_service_account_json')->nullable()->after('ga4_property_id');
        });
    }

    public function down(): void
    {
        Schema::table('seo_settings', function (Blueprint $table): void {
            $table->dropColumn(['ga4_property_id', 'ga4_service_account_json']);
        });
    }
};
