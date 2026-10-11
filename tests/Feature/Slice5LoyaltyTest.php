<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Models\Company;
use Modules\Loyalty\Jobs\EvaluateYearEndTierJob;
use Modules\Loyalty\Jobs\IssueTierBenefitsJob;
use Modules\Loyalty\Jobs\ResetTierPointsJob;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\PointLedger;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Promo\Models\Voucher;

uses(Tests\TestCase::class, RefreshDatabase::class);

function loyaltyAdmin(array $attributes = []): User
{
    $company = Company::factory()->create();

    return User::factory()->create(['company_id' => $company->id, 'company_role' => 'admin', ...$attributes]);
}

function matureLedger(LoyaltyAccount $account, int $points = 1000): PointLedger
{
    return $account->pointLedger()->create([
        'type' => 'earn_base',
        'points' => $points,
        'remaining_points' => $points,
        'counts_for_tier' => true,
        'earned_at' => now()->subDays(31),
        'expires_at' => now()->addYear(),
        'idempotency_key' => fake()->unique()->uuid(),
    ]);
}

test('award dua kali idempoten dan bonus memakai nilai tier server', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::Completed, 'goods_subtotal' => 10000000, 'discount_on_goods' => 500000]);

    app(LoyaltyService::class)->awardForOrder($order);
    app(LoyaltyService::class)->awardForOrder($order);

    expect($account->fresh()->points_balance)->toBe(950)
        ->and($account->pointLedger()->where('order_id', $order->id)->count())->toBe(1);
});

test('poin yang belum matang tidak dapat ditukar', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['points_balance' => 500]);
    $account->pointLedger()->create(['type' => 'earn_base', 'points' => 500, 'remaining_points' => 500, 'earned_at' => now(), 'idempotency_key' => fake()->unique()->uuid()]);

    expect(fn () => app(LoyaltyService::class)->redeem($user, ['type' => 'ewallet', 'points' => 500, 'idempotency_key' => fake()->uuid()]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('redeem klik ganda dengan idempotency key menghasilkan satu redemption', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['points_balance' => 500]);
    matureLedger($account, 500);
    $data = ['type' => 'ewallet', 'points' => 500, 'idempotency_key' => 'same-key'];

    $first = app(LoyaltyService::class)->redeem($user, $data);
    $second = app(LoyaltyService::class)->redeem($user, $data);

    expect($first['redemption']->id)->toBe($second['redemption']->id)->and($account->redemptions()->count())->toBe(1);
});

test('sub akun mendapat 403 saat menukar poin', function (): void {
    $user = loyaltyAdmin(['company_role' => 'member']);
    $this->actingAs($user)->post('/loyalty/redeem', ['type' => 'ewallet', 'points' => 500, 'idempotency_key' => fake()->uuid()])->assertForbidden();
});

test('failed redemption mengembalikan poin lewat adjustment', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['points_balance' => 500]);
    matureLedger($account, 500);
    $redemption = app(LoyaltyService::class)->redeem($user, ['type' => 'ewallet', 'points' => 500, 'idempotency_key' => fake()->uuid()])['redemption'];

    app(LoyaltyService::class)->refundRedemption($redemption, 'Bank menolak');

    expect($account->fresh()->points_balance)->toBe(500)->and($redemption->fresh()->status)->toBe('failed');
});

test('expire points menurunkan saldo dan idempoten', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['points_balance' => 100]);
    $account->pointLedger()->create(['type' => 'earn_base', 'points' => 100, 'remaining_points' => 100, 'earned_at' => now()->subYear(), 'expires_at' => now()->subDay(), 'idempotency_key' => fake()->unique()->uuid()]);

    (new \Modules\Loyalty\Jobs\ExpirePointsJob())->handle();
    (new \Modules\Loyalty\Jobs\ExpirePointsJob())->handle();

    expect($account->fresh()->points_balance)->toBe(0)->and($account->pointLedger()->where('type', 'expire')->count())->toBe(1);
});

test('Bronze hanya mendapat satu voucher per kuartal', function (): void {
    Carbon::setTestNow(Carbon::now()->startOfQuarter());
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['current_tier' => 'bronze']);
    (new IssueTierBenefitsJob())->handle(app(\Modules\Promo\Services\VoucherService::class));
    (new IssueTierBenefitsJob())->handle(app(\Modules\Promo\Services\VoucherService::class));

    expect(Voucher::where('company_id', $user->company_id)->where('source', 'tier_bronze')->count())->toBe(1);
    Carbon::setTestNow();
});

test('Diamond mendapat empat voucher gratis ongkir per bulan')->skip('Ditunda sampai plafon subsidi O-16 untuk Diamond diputuskan.');

test('evaluasi akhir tahun turun maksimal satu level sebelum reset', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['current_tier' => 'gold', 'tier_points' => 500, 'tier_valid_until' => now()->toDateString()]);
    (new EvaluateYearEndTierJob())->handle(app(LoyaltyService::class));

    expect($account->fresh()->current_tier->value)->toBe('silver');
    (new ResetTierPointsJob())->handle(app(LoyaltyService::class));
    expect($account->fresh()->tier_points)->toBe(0);
});

test('saldo poin boleh minus setelah retur', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $account->update(['points_balance' => 0]);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::Completed, 'points_awarded_at' => now()]);
    $account->pointLedger()->create(['order_id' => $order->id, 'type' => 'earn_base', 'points' => 100, 'remaining_points' => 100, 'counts_for_tier' => true, 'earned_at' => now()->subDays(31), 'idempotency_key' => fake()->unique()->uuid()]);

    app(LoyaltyService::class)->reverseForOrder($order);

    expect($account->fresh()->points_balance)->toBe(-100)->and($account->fresh()->tier_points)->toBe(0);
});

test('order dummy price tidak mendapat poin', function (): void {
    $user = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::Completed, 'has_dummy_price_item' => true, 'goods_subtotal' => 1000000]);

    app(LoyaltyService::class)->awardForOrder($order);

    expect($order->fresh()->points_skipped_reason)->toBe('dummy_price')->and($account->fresh()->points_balance)->toBe(0);
});

test('voucher redemption memakai minimum order dan company pemilik', function (): void {
    $user = loyaltyAdmin();
    $other = loyaltyAdmin();
    $account = app(LoyaltyService::class)->createAccount($user->company_id);
    $voucher = Voucher::factory()->create(['company_id' => $user->company_id, 'source' => 'redeem', 'code' => 'LOYALTY-TEST', 'min_order' => 100000]);
    $service = app(\Modules\Promo\Services\PromoService::class);

    expect($service->validateForCompany('LOYALTY-TEST', 100000, $user->company)['voucher']->id)->toBe($voucher->id);
    expect(fn () => $service->validateForCompany('LOYALTY-TEST', 100000, $other->company))->toThrow(\InvalidArgumentException::class);
});
