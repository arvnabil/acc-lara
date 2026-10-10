<?php

namespace Database\Seeders;

use App\Console\Commands\ModulePermissionsSync;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('module:permissions-sync');
        $this->command->info(Artisan::output());
    }
}
