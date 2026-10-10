<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('module permissions sync is idempotent and respects active modules', function () {
    // 1. First sync creates active module permissions
    Artisan::call('module:permissions-sync');
    $initialCount = Permission::count();

    expect($initialCount)->toBeGreaterThan(0);

    // 2. Second sync should not duplicate permissions (idempotent)
    Artisan::call('module:permissions-sync');
    expect(Permission::count())->toBe($initialCount);
});

test('module permissions sync deletes stale permissions from deactivated modules', function () {
    Artisan::call('module:permissions-sync');

    // Create a dummy stale permission
    Permission::create(['name' => 'stale_inactive_permission', 'guard_name' => 'web']);

    expect(Permission::where('name', 'stale_inactive_permission')->exists())->toBeTrue();

    // Re-run sync to revoke permissions not in active modules
    Artisan::call('module:permissions-sync');

    expect(Permission::where('name', 'stale_inactive_permission')->exists())->toBeFalse();
});
