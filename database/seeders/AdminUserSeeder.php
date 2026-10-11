<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (app(ModuleManager::class)->enabled() as $module) {
            foreach ($module->permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@accommerce.id')],
            [
                'name' => env('ADMIN_NAME', 'Admin Accommerce'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ],
        );

        $admin->assignRole($adminRole);

        $this->command?->info("Admin user siap: {$admin->email}");
    }
}
