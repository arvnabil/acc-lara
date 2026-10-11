<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Accounts\Events\CompanyRegistered;
use Modules\Accounts\Models\Address;
use Modules\Accounts\Models\Company;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('individual storefront registration creates a company and admin user', function () {
    Event::fake();

    $response = $this->postJson('/register', [
        'name' => 'Individual Customer',
        'email' => 'customer@example.test',
        'password' => 'secret1234',
    ]);

    $response->assertCreated();

    $user = User::where('email', 'customer@example.test')->firstOrFail();
    $company = Company::findOrFail($user->company_id);

    expect($company->name)->toBe('Individual Customer')
        ->and($company->npwp)->toBeNull()
        ->and($user->company_role)->toBe('admin');

    Event::assertDispatched(CompanyRegistered::class);
});

test('storefront login supports intended redirect and rejects staff without a company', function () {
    $company = Company::factory()->create();
    $customer = User::factory()->create([
        'company_id' => $company->id,
        'password' => Hash::make('secret1234'),
    ]);
    $staff = User::factory()->create(['password' => Hash::make('secret1234')]);

    $this->get('/checkout')->assertRedirect('/login');

    $this->post('/login', [
        'email' => $customer->email,
        'password' => 'secret1234',
    ])->assertRedirect('/checkout');

    $this->post('/logout');

    $this->postJson('/login', [
        'email' => $staff->email,
        'password' => 'secret1234',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('storefront login is rate limited', function () {
    $responses = collect(range(1, 6))->map(fn (): int => $this->postJson('/login', [
        'email' => 'missing@example.test',
        'password' => 'wrong-password',
    ])->status());

    expect($responses->last())->toBe(429);
});

test('forgot password sends the built-in reset notification', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/lupa-password', ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('address endpoints only allow the owning user', function () {
    $owner = User::factory()->create(['company_id' => Company::factory()->create()->id]);
    $other = User::factory()->create(['company_id' => Company::factory()->create()->id]);
    $address = Address::create([
        'user_id' => $other->id,
        'label' => 'Rumah',
        'recipient_name' => 'Other User',
        'phone' => '08123456789',
        'province' => 'Jawa Barat',
        'city' => 'Bandung',
        'address' => 'Jalan Contoh 1',
        'postal_code' => '40111',
        'is_default' => false,
    ]);

    $this->actingAs($owner)
        ->putJson("/akun/alamat/{$address->id}", [
            'label' => 'Kantor',
            'recipient_name' => 'Owner',
            'phone' => '08123456789',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'address' => 'Jalan Lain 2',
            'postal_code' => '40112',
            'is_default' => true,
        ])
        ->assertForbidden();
});

test('guest is redirected to login and logout invalidates the session', function () {
    $this->get('/akun')->assertRedirect('/login');

    $user = User::factory()->create(['company_id' => Company::factory()->create()->id]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    expect(auth()->check())->toBeFalse();
});
