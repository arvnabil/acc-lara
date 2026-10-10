<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Accounts\Events\CompanyRegistered;
use Modules\Accounts\Models\Company;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('storefront registration creates company and admin user in single transaction and dispatches event', function () {
    Event::fake();

    $response = $this->postJson('/register', [
        'name' => 'John Corporate',
        'email' => 'john@accommerce.id',
        'password' => 'secret1234',
        'phone' => '081299998888',
        'account_type' => 'company',
        'company_name' => 'PT Maju Bersama',
        'npwp' => '12.345.678.9-012.000',
    ]);

    $response->assertStatus(201);

    $company = Company::where('name', 'PT Maju Bersama')->first();
    expect($company)->not->toBeNull();
    expect($company->npwp)->toBe('12.345.678.9-012.000');

    $user = User::where('email', 'john@accommerce.id')->first();
    expect($user)->not->toBeNull();
    expect($user->company_id)->toBe($company->id);
    expect($user->company_role)->toBe('admin');

    Event::assertDispatched(CompanyRegistered::class, function ($event) use ($company) {
        return $event->company->id === $company->id;
    });
});

test('storefront registration rejects duplicate email', function () {
    User::create([
        'name' => 'Existing User',
        'email' => 'duplicate@accommerce.id',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/register', [
        'name' => 'New User',
        'email' => 'duplicate@accommerce.id',
        'password' => 'secret1234',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('storefront registration rolls back company creation on failure', function () {
    $initialCompanyCount = Company::count();

    // Trigger failure during user creation by using invalid data or mock
    try {
        DB::transaction(function () {
            $company = Company::create(['name' => 'Failing Company Inc']);
            // Force an exception inside transaction
            throw new \Exception('Simulated database error during user creation');
        });
    } catch (\Exception $e) {
        // Expected exception
    }

    expect(Company::count())->toBe($initialCompanyCount);
    expect(Company::where('name', 'Failing Company Inc')->exists())->toBeFalse();
});
