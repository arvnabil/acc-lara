<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('user without access_panel permission gets 403 on admin panel', function () {
    Permission::firstOrCreate(['name' => 'access_panel', 'guard_name' => 'web']);

    $regularUser = User::create([
        'name' => 'Regular Member',
        'email' => 'regular@accommerce.id',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($regularUser);

    $response = $this->get('/admin');

    $response->assertStatus(403);
});
