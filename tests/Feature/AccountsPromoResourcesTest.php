<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Filament\Resources\CompanyResource;
use Modules\Accounts\Filament\Resources\RoleResource;
use Modules\Accounts\Filament\Resources\UserResource;
use Modules\Accounts\Models\Company;
use Modules\Promo\Filament\Resources\PromoCodeResource;
use Modules\Promo\Filament\Resources\VoucherResource;
use Modules\Promo\Models\PromoCode;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('module:permissions-sync');
});

test('administrator has full resource access while voucher remains read only', function (): void {
    $admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
    $admin->assignRole('administrator');

    $this->actingAs($admin);

    expect(UserResource::canCreate())->toBeTrue()
        ->and(CompanyResource::canCreate())->toBeTrue()
        ->and(RoleResource::canCreate())->toBeTrue()
        ->and(RoleResource::canEdit(Role::where('name', 'administrator')->first()))->toBeTrue()
        ->and(PromoCodeResource::canCreate())->toBeTrue()
        ->and(VoucherResource::canViewAny())->toBeTrue()
        ->and(VoucherResource::canCreate())->toBeFalse();

    foreach (['users', 'companies', 'roles', 'promo-codes', 'vouchers'] as $resource) {
        $this->get("/admin/{$resource}")->assertSuccessful();
    }
});

test('user without resource permission receives forbidden response', function (): void {
    Permission::firstOrCreate(['name' => 'access_panel', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/promo-codes')
        ->assertForbidden();
});

test('promo code CRUD data is persisted and code is unique', function (): void {
    $promo = PromoCode::create([
        'code' => 'SAVE10',
        'label' => 'Diskon 10%',
        'type' => 'percent',
        'value' => 10,
        'min_order' => 0,
        'is_active' => true,
    ]);

    $promo->update(['value' => 15]);
    expect($promo->fresh()->value)->toBe(15);

    expect(fn () => PromoCode::create([
        'code' => 'SAVE10',
        'label' => 'Duplicate',
        'type' => 'percent',
        'value' => 10,
    ]))->toThrow(QueryException::class);

    $promo->delete();
    expect(PromoCode::query()->whereKey($promo->id)->exists())->toBeFalse();
});

test('company NPWP is unique and users expose company context', function (): void {
    $company = Company::create(['name' => 'PT Accommerce', 'npwp' => '01.234.567.8-901.000']);
    $user = User::factory()->create(['company_id' => $company->id, 'company_role' => 'admin']);

    expect($user->fresh()->company->is($company))->toBeTrue()
        ->and($user->fresh()->company_role)->toBe('admin');

    expect(fn () => Company::create(['name' => 'Duplicate NPWP', 'npwp' => '01.234.567.8-901.000']))
        ->toThrow(QueryException::class);
});
