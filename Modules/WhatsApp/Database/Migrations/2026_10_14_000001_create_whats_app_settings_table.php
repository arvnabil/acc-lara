<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('tooltip')->nullable();
            $table->string('position')->default('bottom-right');
            $table->boolean('show_online_badge')->default(true);
            $table->boolean('show_pulse_animation')->default(true);
            $table->boolean('open_in_new_tab')->default(true);
            $table->string('offset_bottom')->default('110px');
            $table->string('offset_side')->default('24px');
            $table->string('icon')->nullable();
            $table->string('button_color')->default('#128C7E');
            $table->json('active_pages')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_settings');
    }
};
