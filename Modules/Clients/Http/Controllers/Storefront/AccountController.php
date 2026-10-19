<?php

namespace Modules\Clients\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TaxProfile;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Clients\Models\Address;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Review\Models\Review;

class AccountController extends Controller
{
    public function show(Request $request, LoyaltyService $loyalty): Response
    {
        $user = $request->user()->load([
            'addresses',
            'client.addresses',
            'client.taxProfiles',
            'client.loyaltyAccount.pointLedger.order',
            'wishlists.product.images',
            'orders.items',
            'orders.items.product',
            'orders.user',
            'orders.client.loyaltyAccount.pointLedger',
            'orders.invoiceRequest.taxProfile',
        ]);

        $account = $loyalty->accountForUser($user);
        $loyaltyProps = $account ? $loyalty->sharedProps($user) : [];
        $vouchers = $user->client?->vouchers()->whereNull('used_at')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderBy('expires_at')->get() ?? collect();
        $reviews = Review::query()->with(['order:id,number', 'product:id,name,slug', 'user:id,name'])->where('client_id', $user->client_id)->latest('id')->get();
        $pendingReviewItems = OrderItem::query()->with(['order:id,number,created_at,client_id,status', 'product:id,name,slug'])->whereHas('order', fn ($query) => $query->where('client_id', $user->client_id)->where('status', OrderStatus::Completed))->whereDoesntHave('reviews', fn ($query) => $query->where('client_id', $user->client_id))->latest('id')->get();

        $addresses = $user->client?->addresses ?? $user->addresses;

        return Inertia::render('Catalog::DashboardPage', [
            'account' => [
                'taxProfiles' => $user->client?->taxProfiles->map(fn (TaxProfile $profile): array => $this->serializeTaxProfile($profile))->values()->all() ?? [],
                'addresses' => $addresses->unique('id')->map(fn (Address $address): array => $this->serializeAddress($address))->values()->all(),
                'orders' => $user->orders->map(fn (Order $order): array => $this->serializeOrder($order))->values()->all(),
                'vouchers' => $vouchers->map(fn ($voucher): array => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'title' => $voucher->title,
                    'type' => $voucher->type,
                    'discount' => (int) $voucher->discount_value,
                    'minOrder' => (int) $voucher->min_order,
                    'expiry' => $voucher->expires_at?->format('d M Y'),
                ])->values()->all(),
                'wishlist' => $user->wishlists->pluck('product_id')->values()->all(),
                'wishlistProducts' => $user->wishlists->pluck('product')->filter()->values()->all(),
                'reviews' => $reviews->map(fn (Review $review): array => $this->serializeReview($review))->values()->all(),
                'pointHistory' => $user->client?->loyaltyAccount?->pointLedger
                    ?->sortByDesc('earned_at')
                    ?->map(fn ($ledger): array => [
                        'type' => $ledger->type,
                        'points' => (int) $ledger->points,
                        'remainingPoints' => (int) ($ledger->remaining_points ?? 0),
                        'earnedAt' => $ledger->earned_at?->toIso8601String(),
                        'availableAt' => ($ledger->available_at ?? $ledger->earned_at?->copy()->addDays((int) config('loyalty.redemption.maturity_days', 7)))?->toIso8601String(),
                        'isClaimable' => $ledger->points > 0
                            && (int) ($ledger->remaining_points ?? 0) > 0
                            && ($ledger->available_at ?? $ledger->earned_at?->copy()->addDays((int) config('loyalty.redemption.maturity_days', 7)))?->isPast(),
                        'notes' => $ledger->notes,
                        'orderNumber' => $ledger->order?->number,
                    ])->values()->all() ?? [],
                'reviewPendingItems' => $pendingReviewItems->map(fn (OrderItem $item): array => [
                    'order' => ['id' => $item->order->number, 'date' => $item->order->created_at?->format('d F Y'), 'status' => 'Selesai'],
                    'item' => ['id' => $item->id, 'name' => $item->product->name, 'productId' => $item->product_id, 'slug' => $item->product->slug, 'qty' => (int) $item->quantity, 'price' => (int) $item->unit_price],
                ])->values()->all(),
                'points_balance' => $loyaltyProps['points_balance'] ?? 0,
                'tier_points' => $loyaltyProps['tier_points'] ?? 0,
                'current_tier' => $loyaltyProps['current_tier'] ?? 'classic',
                ...$loyaltyProps,
                'member_since' => $user->created_at?->format('Y'),
                'reviewsPending' => $pendingReviewItems->count(),
            ],
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $this->validatedAddress($request);
        $user = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $makeDefault = (bool) ($validated['is_default'] ?? false) || ! $user->addresses()->exists();

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([
                ...$validated,
                'client_id' => $user->client_id,
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('success', 'Alamat berhasil disimpan.');
    }

    public function storeTaxProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'npwp' => ['required', 'string', 'max:30'],
            'taxpayer_name' => ['required', 'string', 'max:255'],
            'taxpayer_address' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['sometimes', 'boolean'],
            'npwp_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $client = $request->user()->client()->firstOrFail();

        DB::transaction(function () use ($request, $client, $validated): void {
            $isDefault = (bool) ($validated['is_default'] ?? false) || ! $client->taxProfiles()->exists();
            if ($isDefault) {
                $client->taxProfiles()->update(['is_default' => false]);
            }
            $path = $request->file('npwp_document')?->store('tax-profiles', 'public');
            $client->taxProfiles()->create([
                ...collect($validated)->except('npwp_document', 'is_default')->all(),
                'document_path' => $path,
                'is_default' => $isDefault,
            ]);
        });

        return back()->with('success', 'Data NPWP berhasil disimpan.');
    }

    public function requestInvoice(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->client_id === $request->user()->client_id, 403);
        $validated = $request->validate([
            'tax_profile_id' => ['nullable', 'integer', 'exists:tax_profiles,id'],
            'label' => ['required_without:tax_profile_id', 'string', 'max:80'],
            'npwp' => ['required_without:tax_profile_id', 'string', 'max:30'],
            'taxpayer_name' => ['required_without:tax_profile_id', 'string', 'max:255'],
            'taxpayer_address' => ['nullable', 'string', 'max:2000'],
            'npwp_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $profile = ! empty($validated['tax_profile_id'])
            ? TaxProfile::query()->whereKey($validated['tax_profile_id'])->where('client_id', $request->user()->client_id)->firstOrFail()
            : $request->user()->client->taxProfiles()->create([
                'label' => $validated['label'],
                'npwp' => $validated['npwp'],
                'taxpayer_name' => $validated['taxpayer_name'],
                'taxpayer_address' => $validated['taxpayer_address'] ?? null,
                'document_path' => $request->file('npwp_document')?->store('tax-profiles', 'public'),
                'is_default' => ! $request->user()->client->taxProfiles()->exists(),
            ]);

        $order->invoiceRequest()->updateOrCreate([], [
            'client_id' => $request->user()->client_id,
            'tax_profile_id' => $profile->id,
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Permintaan faktur pajak berhasil dikirim.');
    }

    public function updateAddress(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $validated = $this->validatedAddress($request);

        DB::transaction(function () use ($address, $validated): void {
            if (($validated['is_default'] ?? false) === true) {
                $address->user->addresses()->whereKeyNot($address->getKey())->update(['is_default' => false]);
            }

            $address->update($validated);
        });

        return back()->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $wasDefault = $address->is_default;
        $user = $address->user;

        DB::transaction(function () use ($address, $user, $wasDefault): void {
            $address->delete();

            if ($wasDefault) {
                $user->addresses()->oldest('id')->first()?->update(['is_default' => true]);
            }
        });

        return back()->with('success', 'Alamat berhasil dihapus.');
    }

    public function makeAddressDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);

        DB::transaction(function () use ($address): void {
            $address->user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('success', 'Alamat utama berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAddress(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'province' => ['required', 'string', 'max:100'],
            'province_id' => ['nullable', 'integer'],
            'province_name' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'city_id' => ['nullable', 'integer'],
            'city_name' => ['nullable', 'string', 'max:150'],
            'district_id' => ['nullable', 'integer'],
            'district_name' => ['nullable', 'string', 'max:150'],
            'subdistrict_id' => ['nullable', 'integer'],
            'subdistrict_name' => ['nullable', 'string', 'max:150'],
            'destination_id' => ['nullable', 'integer'],
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'string', 'max:10'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $validated['province_name'] ??= $validated['province'];
        $validated['city_name'] ??= $validated['city'];

        return $validated;
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id || $address->client_id === $request->user()->client_id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipientName' => $address->recipient_name,
            'phone' => $address->phone,
            'province' => $address->province,
            'provinceId' => $address->province_id,
            'provinceName' => $address->province_name ?: $address->province,
            'city' => $address->city,
            'cityId' => $address->city_id,
            'cityName' => $address->city_name ?: $address->city,
            'districtId' => $address->district_id,
            'districtName' => $address->district_name,
            'subdistrictId' => $address->subdistrict_id,
            'subdistrictName' => $address->subdistrict_name,
            'destinationId' => $address->destination_id,
            'address' => $address->address,
            'postalCode' => $address->postal_code,
            'isDefault' => $address->is_default,
        ];
    }

    private function serializeTaxProfile(TaxProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'label' => $profile->label,
            'npwp' => $profile->npwp,
            'taxpayerName' => $profile->taxpayer_name,
            'taxpayerAddress' => $profile->taxpayer_address,
            'documentAvailable' => filled($profile->document_path),
            'isDefault' => $profile->is_default,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(Order $order): array
    {
        $address = $order->shipping_address ?? [];

        return [
            'id' => $order->number,
            'number' => $order->number,
            'invoiceNo' => $order->number,
            'orderRef' => $order->number,
            'date' => $order->created_at?->format('d F Y'),
            'status' => match ($order->status?->value) {
                'pending_payment' => 'Menunggu Pembayaran',
                'awaiting_verification' => 'Menunggu Verifikasi',
                'paid' => 'Dibayar',
                'processing' => 'Diproses',
                'packed' => 'Dikemas',
                'shipped' => 'Dalam Pengiriman',
                'completed' => 'Selesai',
                'returned' => 'Dikembalikan',
                'cancelled' => 'Dibatalkan',
                default => $order->status?->value,
            },
            'status_value' => $order->status?->value,
            'title' => $order->items->first()?->product_name ?? 'Pesanan Accommerce',
            'total' => (int) $order->grand_total,
            'paymentMethod' => $order->payment_method,
            'recipientName' => $address['recipient'] ?? null,
            'streetAddress' => $address['address'] ?? null,
            'city' => $address['city'] ?? null,
            'province' => $address['province'] ?? null,
            'postalCode' => $address['postal_code'] ?? null,
            'email' => $order->user?->email,
            'phone' => $address['phone'] ?? $order->user?->phone,
            'shippingCost' => (int) $order->shipping_cost,
            'goodsSubtotal' => (int) $order->goods_subtotal,
            'discountOnGoods' => (int) $order->discount_on_goods,
            'ppn' => (int) $order->ppn,
            'adminFee' => (int) $order->admin_fee,
            'pointsAwardedAt' => $order->points_awarded_at?->toIso8601String(),
            'pointsSkippedReason' => $order->points_skipped_reason,
            'pointsEarned' => (int) ($order->client?->loyaltyAccount?->pointLedger
                ?->where('order_id', $order->id)
                ?->sum('points') ?? 0),
            'pointsHistory' => $order->client?->loyaltyAccount?->pointLedger
                ?->where('order_id', $order->id)
                ?->sortByDesc('earned_at')
                ?->map(fn ($ledger): array => [
                    'type' => $ledger->type,
                    'points' => (int) $ledger->points,
                    'earnedAt' => $ledger->earned_at?->toIso8601String(),
                    'notes' => $ledger->notes,
                ])->values()->all() ?? [],
            'invoiceRequest' => $order->invoiceRequest ? [
                'status' => $order->invoiceRequest->status,
                'taxProfileId' => $order->invoiceRequest->tax_profile_id,
                'taxpayerName' => $order->invoiceRequest->taxProfile?->taxpayer_name,
                'npwp' => $order->invoiceRequest->taxProfile?->npwp,
            ] : null,
            'shippingEstimate' => $order->shipping_estimate,
            'shippingLabel' => $order->shipping_label,
            'trackingNumber' => $order->tracking_number,
            'biteshipTrackingId' => $order->biteship_tracking_id,
            'biteshipWaybillId' => $order->biteship_waybill_id,
            'biteshipStatus' => $order->biteship_status,
            'biteshipTrackingUrl' => $order->biteship_tracking_url,
            'biteshipTrackingHistory' => $order->biteship_tracking_history,
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'slug' => $item->product?->slug,
                'name' => $item->product_name,
                'sku' => $item->sku,
                'qty' => (int) $item->quantity,
                'price' => (int) $item->unit_price,
                'weight' => null,
            ])->values()->all(),
        ];
    }

    private function serializeReview(Review $review): array
    {
        $media = collect($review->media ?? []);

        return [
            'id' => $review->id,
            'orderId' => $review->order?->number,
            'productName' => $review->product?->name,
            'productSlug' => $review->product?->slug,
            'rating' => (int) $review->rating,
            'comment' => $review->body,
            'isAnonymous' => (bool) $review->is_anonymous,
            'userName' => $review->is_anonymous ? 'Pengguna Anonim' : $review->user?->name,
            'date' => $review->created_at?->translatedFormat('d F Y'),
            'images' => $media->where('type', 'photo')->map(fn (array $item): string => Storage::disk('public')->url($item['path']))->values()->all(),
            'video' => ($video = $media->firstWhere('type', 'video')) ? Storage::disk('public')->url($video['path']) : null,
        ];
    }
}
