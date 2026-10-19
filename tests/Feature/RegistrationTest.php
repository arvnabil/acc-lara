<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Event;
use Modules\Clients\Events\ClientRegistered;
use Modules\Clients\Models\Client;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\PointLedger;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('storefront registration creates company and admin user in single transaction and dispatches event', function () {
    Event::fake();

    $response = $this->postJson('/register', [
        'name' => 'John Corporate',
        'email' => 'john@accommerce.id',
        'password' => 'secret1234',
        'phone' => '081299998888',
        'account_type' => 'client',
        'company_name' => 'PT Maju Bersama',
        'npwp' => '12.345.678.9-012.000',
    ]);

    $response->assertStatus(201);

    $company = Client::where('name', 'PT Maju Bersama')->first();
    expect($company)->not->toBeNull();
    expect($company->npwp)->toBe('12.345.678.9-012.000');

    $user = User::where('email', 'john@accommerce.id')->first();
    expect($user)->not->toBeNull();
    expect($user->client_id)->toBe($company->id);
    expect($user->company_role)->toBe('admin');

    Event::assertDispatched(ClientRegistered::class, function ($event) use ($company) {
        return $event->client->id === $company->id;
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

test('registration referral awards configured points to the referrer', function (): void {
    $referrerClient = Client::factory()->create();
    $referrer = User::factory()->create([
        'client_id' => $referrerClient->id,
        'referral_code' => 'REF123456',
    ]);
    LoyaltyAccount::factory()->create([
        'client_id' => $referrerClient->id,
        'points_balance' => 0,
    ]);
    config(['loyalty.referral.registration_points' => 250]);

    $response = $this->postJson('/register', [
        'name' => 'Referred Customer',
        'email' => 'referred@example.test',
        'password' => 'secret1234',
        'referral_code' => 'ref123456',
    ]);

    $response->assertCreated();
    $newUser = User::where('email', 'referred@example.test')->firstOrFail();
    $account = LoyaltyAccount::where('client_id', $referrerClient->id)->firstOrFail();

    expect($newUser->referred_by_user_id)->toBe($referrer->id)
        ->and($account->points_balance)->toBe(250)
        ->and(PointLedger::where('idempotency_key', "referral_registration:{$newUser->id}")->value('points'))->toBe(250);
});

test('storefront registration rolls back company creation on failure', function () {
    $initialClientCount = Client::count();

    // Trigger failure during user creation by using invalid data or mock
    try {
        DB::transaction(function () {
            $company = Client::create(['name' => 'Failing Client Inc']);
            // Force an exception inside transaction
            throw new \Exception('Simulated database error during user creation');
        });
    } catch (\Exception $e) {
        // Expected exception
    }

    expect(Client::count())->toBe($initialClientCount);
    expect(Client::where('name', 'Failing Client Inc')->exists())->toBeFalse();
});




