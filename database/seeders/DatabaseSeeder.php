<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Sync permissions dari modul aktif
        $this->call(PermissionSeeder::class);

        // 2. Buat role administrator dengan semua permission
        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // 3. Buat super-admin user untuk panel Filament
        $admin = User::firstOrCreate(
            ['email' => 'admin@accommerce.id'],
            [
                'name'     => 'Admin Accommerce',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('administrator');
    }
}
