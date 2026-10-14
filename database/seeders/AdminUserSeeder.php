<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminName = env('ADMIN_NAME', 'Admin Accommerce');
        $secondaryEmail = env('NABIL_ADMIN_EMAIL');
        $secondaryPassword = env('NABIL_ADMIN_PASSWORD');
        $secondaryName = env('NABIL_ADMIN_NAME', 'Nabil');

        if (app()->environment('production') && (! $adminEmail || ! $adminPassword)) {
            throw new \RuntimeException('ADMIN_EMAIL and ADMIN_PASSWORD are required in production.');
        }

        if (! $adminEmail || ! $adminPassword) {
            $adminEmail ??= 'admin-'.Str::lower(Str::random(8)).'@local.invalid';
            $adminPassword ??= Str::random(32);
            $this->command?->warn("Admin lokal dibuat dengan kredensial sementara: {$adminEmail} / {$adminPassword}");
        }

        foreach (app(ModuleManager::class)->enabled() as $module) {
            foreach ($module->permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
            ],
        );

        $admin->assignRole($adminRole);

        $this->command?->info("Admin user siap: {$admin->email}");

        if ($secondaryEmail && $secondaryPassword) {
            $secondary = User::updateOrCreate(
                ['email' => $secondaryEmail],
                ['name' => $secondaryName, 'password' => Hash::make($secondaryPassword)],
            );
            $secondary->assignRole($adminRole);
            $this->command?->info("Admin user siap: {$secondary->email}");
        }
    }
}
