<?php

namespace Database\Seeders;

use App\LoyaltySetting;
use Illuminate\Database\Seeder;

class LoyaltySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('loyalty');
        $defaults['tiers'] = [
            'classic' => ['label' => 'Classic', 'min' => 0, 'max' => 499, 'spend_min' => 0, 'spend_max' => 4990000, 'bonus_percent' => 0, 'perks' => ['Akun member terverifikasi & katalog lengkap', 'Bonus 50 Poin per ulasan pembelian terverifikasi', 'Faktur Pajak PKP (bagi member ber-NPWP/SPPKP)', 'Akses promo reguler Accommerce']],
            'bronze' => ['label' => 'Bronze', 'min' => 500, 'max' => 1499, 'spend_min' => 5000000, 'spend_max' => 14990000, 'bonus_percent' => 0, 'perks' => ['Semua benefit Classic', 'Voucher potongan Rp 25.000 / diskon khusus aksesori', 'Gratis ongkir 1x/bulan (s/d Rp 30.000)', 'Pengiriman pesanan prioritas reguler']],
            'silver' => ['label' => 'Silver', 'min' => 1500, 'max' => 7499, 'spend_min' => 15000000, 'spend_max' => 74990000, 'bonus_percent' => 5, 'perks' => ['Semua benefit Bronze', 'Bonus 5% Poin belanja per order', 'Gratis ongkir 2x/bulan (s/d Rp 50.000)', 'Layanan konsultasi teknis audio visual']],
            'gold' => ['label' => 'Gold', 'min' => 7500, 'max' => 14999, 'spend_min' => 75000000, 'spend_max' => 149990000, 'bonus_percent' => 10, 'perks' => ['Semua benefit Silver', 'Harga khusus proyek B2B (Project Pricing)', 'Prioritas alokasi stok hardware IT & AV', 'Gratis ongkir 4x/bulan & Flash Sale VIP', 'Bonus 10% Poin belanja per order']],
            'platinum' => ['label' => 'Platinum', 'min' => 15000, 'max' => 39999, 'spend_min' => 150000000, 'spend_max' => 399990000, 'bonus_percent' => 20, 'perks' => ['Semua benefit Gold', 'Dedicated Account Manager B2B', 'Termin pembayaran B2B (NET 14 Hari)', 'Dukungan garansi & RMA express prioritas', 'Bonus 20% Poin belanja per order']],
            'diamond' => ['label' => 'Diamond', 'min' => 40000, 'max' => null, 'spend_min' => 400000000, 'spend_max' => null, 'bonus_percent' => 30, 'perks' => ['Semua benefit Platinum (Top Executive Tier)', 'Termin pembayaran B2B (NET 30 Hari)', 'Prioritas pengiriman instan same-day', 'Hotline teknis 24/7 & asistensi instalasi on-site', 'Bonus 30% Poin belanja per order', 'Hadiah corporate anniversary eksklusif']],
        ];

        $setting = LoyaltySetting::firstOrCreate([], ['settings' => $defaults]);
        $setting->update(['settings' => array_replace_recursive($defaults, $setting->settings ?? [])]);
    }
}
