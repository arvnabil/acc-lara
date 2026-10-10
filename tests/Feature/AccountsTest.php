<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Accounts\Events\CompanyRegistered;
use Modules\Accounts\Models\Address;
use Modules\Accounts\Models\Company;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('user can belong to a company and have addresses', function () {
    $company = Company::create([
        'name' => 'PT Accommerce Indonesia',
        'npwp' => '01.234.567.8-901.000',
        'phone' => '021-5551234',
        'email' => 'corporate@accommerce.id',
    ]);

    $user = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi@accommerce.id',
        'password' => bcrypt('secret123'),
        'phone' => '081234567890',
        'company_id' => $company->id,
        'company_role' => 'admin',
    ]);

    $address = Address::create([
        'user_id' => $user->id,
        'label' => 'Kantor Utama',
        'recipient_name' => 'Budi Santoso',
        'phone' => '081234567890',
        'province' => 'DKI Jakarta',
        'city' => 'Jakarta Selatan',
        'address' => 'Jl. Jendral Sudirman No. 10',
        'postal_code' => '12190',
        'is_default' => true,
    ]);

    expect($user->company->id)->toBe($company->id);
    expect($company->users)->toHaveCount(1);
    expect($user->addresses)->toHaveCount(1);
    expect($user->addresses->first()->city)->toBe('Jakarta Selatan');
});

test('administrator role passes gate before and can access panel', function () {
    Permission::firstOrCreate(['name' => 'access_panel', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);

    $admin = User::create([
        'name' => 'Admin Test',
        'email' => 'admin.test@accommerce.id',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('administrator');

    expect($admin->hasRole('administrator'))->toBeTrue();
    expect($admin->can('access_panel'))->toBeTrue();
    expect($admin->canAccessPanel(new \Filament\Panel()))->toBeTrue();
});

test('company registered event can be dispatched', function () {
    Event::fake();

    $company = Company::create([
        'name' => 'PT Sukses Jaya',
    ]);

    CompanyRegistered::dispatch($company);

    Event::assertDispatched(CompanyRegistered::class, function ($event) use ($company) {
        return $event->company->id === $company->id;
    });
});
