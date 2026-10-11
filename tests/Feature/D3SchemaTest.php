<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Models\Company;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Cart\Models\Wishlist;
use Modules\Content\Models\Banner;
use Modules\Content\Models\RfqRequest;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Models\PointLedger;
use Modules\Loyalty\Models\Redemption;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Models\OrderStatusHistory;
use Modules\Orders\Models\PaymentProof;
use Modules\Promo\Models\Voucher;
use Modules\Review\Models\Review;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('every D3 factory creates valid persisted data', function (): void {
    expect(Cart::factory()->create())->toBeInstanceOf(Cart::class)
        ->and(CartItem::factory()->create())->toBeInstanceOf(CartItem::class)
        ->and(Wishlist::factory()->create())->toBeInstanceOf(Wishlist::class)
        ->and(Order::factory()->create())->toBeInstanceOf(Order::class)
        ->and(OrderItem::factory()->create())->toBeInstanceOf(OrderItem::class)
        ->and(OrderStatusHistory::factory()->create())->toBeInstanceOf(OrderStatusHistory::class)
        ->and(PaymentProof::factory()->create())->toBeInstanceOf(PaymentProof::class)
        ->and(LoyaltyAccount::factory()->create())->toBeInstanceOf(LoyaltyAccount::class)
        ->and(PointLedger::factory()->create())->toBeInstanceOf(PointLedger::class)
        ->and(Redemption::factory()->create())->toBeInstanceOf(Redemption::class)
        ->and(Review::factory()->create())->toBeInstanceOf(Review::class)
        ->and(Banner::factory()->create())->toBeInstanceOf(Banner::class)
        ->and(RfqRequest::factory()->create())->toBeInstanceOf(RfqRequest::class);
});

test('RESTRICT foreign keys reject deleting referenced parents', function (): void {
    $company = Company::factory()->create();
    LoyaltyAccount::factory()->for($company)->create();
    expect(fn () => $company->delete())->toThrow(QueryException::class);

    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create();
    expect(fn () => $order->delete())->toThrow(QueryException::class);

});

test('order voucher FK is RESTRICT to preserve financial history', function (): void {
    $voucher = Voucher::factory()->create();
    Order::factory()->create(['voucher_id' => $voucher->id]);
    expect(fn () => $voucher->delete())->toThrow(QueryException::class);
});

test('voucher quota uniques preserve nullable redeem keys', function (): void {
    $company = Company::factory()->create();
    Voucher::factory()->create(['company_id' => $company->id, 'source' => 'tier_bronze', 'quarter_key' => '2026-Q4']);
    expect(fn () => Voucher::factory()->create(['company_id' => $company->id, 'source' => 'tier_bronze', 'quarter_key' => '2026-Q4']))->toThrow(QueryException::class);

    Voucher::factory()->create(['company_id' => $company->id, 'source' => 'free_shipping', 'month_key' => '2026-10', 'slot' => 1]);
    expect(fn () => Voucher::factory()->create(['company_id' => $company->id, 'source' => 'free_shipping', 'month_key' => '2026-10', 'slot' => 1]))->toThrow(QueryException::class);

    Voucher::factory()->create(['company_id' => $company->id, 'source' => 'redeem', 'quarter_key' => null]);
    Voucher::factory()->create(['company_id' => $company->id, 'source' => 'redeem', 'quarter_key' => null]);
    expect(Voucher::where('source', 'redeem')->count())->toBe(2);
});

test('point ledger and redemption idempotency keys are unique', function (): void {
    $ledger = PointLedger::factory()->create(['idempotency_key' => 'same-ledger-key']);
    expect(fn () => PointLedger::factory()->create(['idempotency_key' => $ledger->idempotency_key]))->toThrow(QueryException::class);

    $redemption = Redemption::factory()->create(['idempotency_key' => 'same-redemption-key']);
    expect(fn () => Redemption::factory()->create(['idempotency_key' => $redemption->idempotency_key]))->toThrow(QueryException::class);
});

test('database enums reject values outside the ERD lists', function (): void {
    $company = Company::factory()->create();
    $user = App\Models\User::factory()->create();
    expect(fn () => DB::table('orders')->insert([
        'number' => 'BAD-ENUM-1', 'user_id' => $user->id, 'company_id' => $company->id, 'status' => 'not_a_status',
        'goods_subtotal' => 1, 'grand_total' => 1, 'shipping_address' => json_encode(['address' => 'x']),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('loyalty_accounts')->insert([
        'company_id' => $company->id, 'current_tier' => 'not_a_tier', 'tier_valid_until' => now()->addYear(),
    ]))->toThrow(QueryException::class);

    $account = LoyaltyAccount::factory()->create();
    expect(fn () => DB::table('point_ledger')->insert([
        'loyalty_account_id' => $account->id, 'type' => 'not_a_ledger_type', 'points' => 1, 'earned_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('migration fresh and rollback down succeed on the MySQL test database', function (): void {
    expect(Artisan::call('migrate:fresh', ['--database' => 'mysql']))->toBe(0)
        ->and(Artisan::call('migrate:rollback', ['--database' => 'mysql']))->toBe(0)
        ->and(Artisan::call('migrate:fresh', ['--database' => 'mysql']))->toBe(0);
});
