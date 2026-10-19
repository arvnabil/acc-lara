<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat role administrator dan user admin dari .env.
        $this->call(AdminUserSeeder::class);
        $this->call(LoyaltySettingsSeeder::class);

        // 2. Lima kode promo dasar dari spesifikasi checkout.
        $this->call(\Modules\Promo\Database\Seeders\PromoSeeder::class);

        $this->call(\Modules\Content\Database\Seeders\BannerSeeder::class);

        // 3. Seed catalog data dari Front/src/data (Skip during tests to save time)
        if (!app()->environment('testing')) {
            $this->call(\Modules\Catalog\Database\Seeders\CatalogSeeder::class);
        }
    }
}
