<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->whereIn('name', ['create_company', 'delete_company', 'update_company', 'view_any_company', 'view_company'])
            ->get()
            ->each(function (object $permission): void {
                DB::table('permissions')->where('id', $permission->id)->update([
                    'name' => str_replace('_company', '_client', $permission->name),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('name', ['create_client', 'delete_client', 'update_client', 'view_any_client', 'view_client'])
            ->get()
            ->each(function (object $permission): void {
                DB::table('permissions')->where('id', $permission->id)->update([
                    'name' => str_replace('_client', '_company', $permission->name),
                ]);
            });
    }
};
