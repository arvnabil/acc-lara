<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('npwp', 30);
            $table->string('taxpayer_name');
            $table->text('taxpayer_address')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['client_id', 'npwp']);
        });

        DB::table('clients')->whereNotNull('npwp')->get(['id', 'npwp', 'name', 'address'])->each(function (object $client): void {
            DB::table('tax_profiles')->insert([
                'client_id' => $client->id,
                'label' => 'NPWP Utama',
                'npwp' => $client->npwp,
                'taxpayer_name' => $client->name,
                'taxpayer_address' => $client->address,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_profiles');
    }
};
