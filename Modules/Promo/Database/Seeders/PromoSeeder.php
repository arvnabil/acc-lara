<?php

namespace Modules\Promo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Promo\Models\PromoCode;

class PromoSeeder extends Seeder
{
    public function run(): void
    {
        $promos = [
            ['code' => 'SAVE10', 'label' => 'Diskon 10%', 'type' => 'percent', 'value' => 10],
            ['code' => 'ACCOMM25', 'label' => 'Diskon 25%', 'type' => 'percent', 'value' => 25],
            ['code' => 'GRATIS50K', 'label' => 'Potongan Rp 50.000 + subsidi ongkir', 'type' => 'fixed', 'value' => 50000],
            ['code' => 'TECH100K', 'label' => 'Potongan langsung Rp 100.000', 'type' => 'fixed', 'value' => 100000],
            ['code' => 'NEWMEMBER', 'label' => 'Diskon member baru 15%', 'type' => 'percent', 'value' => 15],
        ];

        foreach ($promos as $promo) {
            PromoCode::updateOrCreate(
                ['code' => $promo['code']],
                [...$promo, 'min_order' => 0, 'is_active' => true],
            );
        }
    }
}
