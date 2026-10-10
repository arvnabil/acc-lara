<?php

namespace App\Console\Commands;

use App\Support\Modules\ModuleManager;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionsSync extends Command
{
    protected $signature = 'module:permissions-sync';
    protected $description = 'Sinkronisasi permissions dari modul aktif ke Spatie Permission (idempoten)';

    public function handle(ModuleManager $modules, PermissionRegistrar $registrar): int
    {
        $this->info('Memulai sinkronisasi permissions dari modul aktif...');

        // 1. Kumpulkan permissions dari semua modul yang AKTIF
        $activePermissions = [];
        foreach ($modules->enabled() as $module) {
            foreach ($module->permissions as $permission) {
                $activePermissions[] = $permission;
            }
        }
        $activePermissions = array_values(array_unique($activePermissions));

        // 2. Buat atau temukan setiap permission (Permission::findOrCreate)
        $createdCount = 0;
        foreach ($activePermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if (! $permission) {
                Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                $createdCount++;
            }
        }

        // 3. Hapus permission yang berasal dari modul yang dinonaktifkan atau dihapus
        $stalePermissions = Permission::whereNotIn('name', $activePermissions)->get();
        $deletedCount = $stalePermissions->count();

        foreach ($stalePermissions as $stale) {
            $stale->delete();
        }

        // 4. Reset cache permission Spatie
        $registrar->forgetCachedPermissions();

        $this->info("Sinkronisasi selesai: {$createdCount} dibuat, {$deletedCount} dihapus, " . count($activePermissions) . " total izin aktif.");

        return self::SUCCESS;
    }
}
