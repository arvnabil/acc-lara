<?php

use App\Models\User;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\PermissionSeeder::class]);
});

test('only users with permission can access catalog resources', function () {
    $admin = User::factory()->create();
    $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
    $adminRole->syncPermissions(Permission::all());
    $admin->assignRole('administrator');

    $user = User::factory()->create();

    // Admin should see resources
    $this->actingAs($admin)
        ->get('/admin/brands')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/categories')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/products')
        ->assertSuccessful();

    // Regular user should not
    $this->actingAs($user)
        ->get('/admin/brands')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/categories')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/products')
        ->assertForbidden();
});
