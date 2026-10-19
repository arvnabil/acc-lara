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
        Schema::table('addresses', function (Blueprint $table): void {
            $table->unsignedBigInteger('province_id')->nullable()->after('province');
            $table->string('province_name', 150)->nullable()->after('province_id');
            $table->unsignedBigInteger('city_id')->nullable()->after('city');
            $table->string('city_name', 150)->nullable()->after('city_id');
            $table->unsignedBigInteger('district_id')->nullable()->after('city_name');
            $table->string('district_name', 150)->nullable()->after('district_id');
            $table->unsignedBigInteger('subdistrict_id')->nullable()->after('district_name');
            $table->string('subdistrict_name', 150)->nullable()->after('subdistrict_id');
            $table->unsignedBigInteger('destination_id')->nullable()->after('subdistrict_name');
            $table->index('destination_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex('addresses_destination_id_index');
            $table->dropColumn([
                'province_id',
                'province_name',
                'city_id',
                'city_name',
                'district_id',
                'district_name',
                'subdistrict_id',
                'subdistrict_name',
                'destination_id',
            ]);
        });
    }
};
