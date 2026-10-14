<?php

namespace Modules\Content\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Content\Models\Banner;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            ['title' => 'Diskon Yealink', 'image_url' => '/banner/diskon-yealink.png', 'target_url' => '/katalog?brand=yealink', 'sort_order' => 1],
            ['title' => 'October Sale', 'image_url' => '/banner/october-sale.png', 'target_url' => '/katalog?sort=sale', 'sort_order' => 2],
            ['title' => 'Zoom Video Collaboration', 'image_url' => '/banner/zoom.png', 'target_url' => '/katalog?q=zoom', 'sort_order' => 3],
            ['title' => 'Zoom Education Solution', 'image_url' => '/banner/zoom-educate.png', 'target_url' => '/katalog?q=zoom', 'sort_order' => 4],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['image_url' => $banner['image_url']],
                [...$banner, 'is_active' => true, 'mobile_image_url' => null, 'starts_at' => null, 'expires_at' => null],
            );
        }
    }
}
