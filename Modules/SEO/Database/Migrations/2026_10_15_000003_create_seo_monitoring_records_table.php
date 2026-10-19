<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_monitoring_records', function (Blueprint $table): void {
            $table->id();
            $table->string('url')->unique();
            $table->string('model');
            $table->unsignedBigInteger('model_id');
            $table->boolean('is_noindex')->default(false);
            $table->boolean('in_sitemap')->default(false);
            $table->boolean('canonical_valid')->default(true);
            $table->unsignedTinyInteger('seo_score')->default(0);
            $table->timestamp('last_modified')->nullable();
            $table->timestamps();
            $table->index(['model', 'model_id']);
            $table->index('seo_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_monitoring_records');
    }
};
