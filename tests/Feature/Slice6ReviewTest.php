<?php

use App\Models\User;
use App\Support\Events\ReviewStored;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Accounts\Models\Company;
use Modules\Catalog\Models\Product;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Services\OrderStateMachine;
use Modules\Review\Models\Review;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function reviewCustomer(?Company $company = null, array $attributes = []): User
{
    $company ??= Company::factory()->create();

    return User::factory()->create(['company_id' => $company->id, 'company_role' => 'admin', ...$attributes]);
}

function completedReviewFixture(?User $user = null, array $orderAttributes = []): array
{
    $user ??= reviewCustomer();
    app(LoyaltyService::class)->createAccount($user->company_id);
    $product = Product::factory()->create(['slug' => fake()->unique()->slug()]);
    $order = Order::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id, 'status' => OrderStatus::Completed, ...$orderAttributes]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name]);

    return compact('user', 'product', 'order', 'item');
}

function reviewPayload(OrderItem $item, array $extra = []): array
{
    return ['order_item_id' => $item->id, 'rating' => 5, 'body' => 'Ulasan terverifikasi yang cukup panjang.', ...$extra];
}

test('ulasan hanya dapat dibuat dari pesanan completed', function (string $status): void {
    $fixture = completedReviewFixture(null, ['status' => $status]);

    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item']))->assertStatus(422);
    expect(Review::count())->toBe(0);
})->with([OrderStatus::Returned->value, OrderStatus::Cancelled->value]);

test('ulasan dari item company lain ditolak', function (): void {
    $fixture = completedReviewFixture();
    $other = reviewCustomer();

    $this->actingAs($other)->post('/reviews', reviewPayload($fixture['item']))->assertForbidden();
    expect(Review::count())->toBe(0);
});

test('sub akun boleh mengulas pertama kali tetapi review kedua company ditolak', function (): void {
    $fixture = completedReviewFixture();
    $member = reviewCustomer($fixture['user']->company, ['company_role' => 'member']);

    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item']))->assertRedirect();
    $secondOrder = Order::factory()->create(['user_id' => $member->id, 'company_id' => $member->company_id, 'status' => OrderStatus::Completed]);
    $secondItem = OrderItem::factory()->create(['order_id' => $secondOrder->id, 'product_id' => $fixture['product']->id, 'product_name' => $fixture['product']->name]);

    $this->actingAs($member)->post('/reviews', reviewPayload($secondItem))->assertSessionHasErrors('order_item_id');
    expect(Review::where('company_id', $fixture['user']->company_id)->count())->toBe(1);
});

test('review memberi lima puluh poin sekali dan tier points tidak berubah', function (): void {
    $fixture = completedReviewFixture();
    $account = LoyaltyAccount::where('company_id', $fixture['user']->company_id)->firstOrFail();
    $account->update(['tier_points' => 25, 'points_balance' => 0]);

    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item']))->assertRedirect();
    $review = Review::firstOrFail();
    event(new ReviewStored($review->id, $review->company_id, $review->user_id, $review->product_id, $review->order_id));

    expect($account->fresh()->points_balance)->toBe(50)
        ->and($account->fresh()->tier_points)->toBe(25)
        ->and($account->pointLedger()->where('type', 'earn_review')->count())->toBe(1);
});

test('retur menarik poin ulasan', function (): void {
    $fixture = completedReviewFixture();
    $account = LoyaltyAccount::where('company_id', $fixture['user']->company_id)->firstOrFail();
    $account->update(['points_balance' => 0]);
    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item']))->assertRedirect();

    app(OrderStateMachine::class)->transition($fixture['order'], OrderStatus::Returned);

    expect($account->fresh()->points_balance)->toBe(0)
        ->and((int) $account->pointLedger()->where('type', 'reversal')->sum('points'))->toBe(-50);
});

test('review anonim tidak membocorkan nama email atau user id ke publik', function (): void {
    $fixture = completedReviewFixture();
    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item'], ['is_anonymous' => true]))->assertRedirect();

    $response = $this->get('/produk/'.$fixture['product']->slug);
    $response->assertInertia(fn ($page) => $page
        ->where('product.reviews.0.userName', 'Pengguna Anonim')
        ->where('product.reviews.0.isAnonymous', true)
        ->missing('product.reviews.0.email')
        ->missing('product.reviews.0.user_id'));
});

test('media review menolak mime palsu dan ukuran video berlebih', function (): void {
    $fixture = completedReviewFixture();
    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item'], ['photos' => [UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')]]))->assertSessionHasErrors('photos.0');
    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item'], ['video' => UploadedFile::fake()->create('large.mp4', 25601, 'video/mp4')]))->assertSessionHasErrors('video');
});

test('review yang disembunyikan tidak tampil publik tetapi tetap ada di admin', function (): void {
    $fixture = completedReviewFixture();
    $this->actingAs($fixture['user'])->post('/reviews', reviewPayload($fixture['item']))->assertRedirect();
    $review = Review::firstOrFail();
    $review->update(['is_approved' => false]);

    $this->get('/produk/'.$fixture['product']->slug)->assertInertia(fn ($page) => $page->where('product.review_summary.count', 0)->where('product.reviews', []));
    expect(Review::find($review->id))->not->toBeNull();
});

test('rata rata dan jumlah review publik benar', function (): void {
    $first = completedReviewFixture();
    $secondUser = reviewCustomer();
    $secondOrder = Order::factory()->create(['user_id' => $secondUser->id, 'company_id' => $secondUser->company_id, 'status' => OrderStatus::Completed]);
    $secondItem = OrderItem::factory()->create(['order_id' => $secondOrder->id, 'product_id' => $first['product']->id, 'product_name' => $first['product']->name]);
    Review::create(['order_id' => $first['order']->id, 'order_item_id' => $first['item']->id, 'product_id' => $first['product']->id, 'user_id' => $first['user']->id, 'company_id' => $first['user']->company_id, 'rating' => 4, 'body' => 'Bagus']);
    Review::create(['order_id' => $secondOrder->id, 'order_item_id' => $secondItem->id, 'product_id' => $first['product']->id, 'user_id' => $secondUser->id, 'company_id' => $secondUser->company_id, 'rating' => 2, 'body' => 'Cukup']);

    $this->get('/produk/'.$first['product']->slug)->assertInertia(fn ($page) => $page->where('product.review_summary.count', 2)->where('product.review_summary.average', 3));
});
