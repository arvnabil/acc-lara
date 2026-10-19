<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('ga4_id')->nullable();
            $table->string('gtm_id')->nullable();
            $table->string('search_console_verification')->nullable();
            $table->string('default_title')->nullable();
            $table->text('default_description')->nullable();
            $table->string('favicon')->nullable();
            $table->string('default_og_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
