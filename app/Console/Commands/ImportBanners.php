<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Content\Database\Seeders\BannerSeeder;
use Modules\Content\Models\Banner;

#[Signature('content:import-banners')]
#[Description('Import banner hardcode homepage ke database banners')]
class ImportBanners extends Command
{
    public function handle(BannerSeeder $bannerSeeder): int
    {
        $bannerSeeder->run();

        $count = Banner::query()->count();

        $this->info("Import banner selesai. Total banner: {$count}.");

        return self::SUCCESS;
    }
}
