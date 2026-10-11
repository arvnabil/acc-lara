<?php

namespace Modules\Accounts\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounts\Models\Address;
use Modules\Orders\Models\Order;
use Modules\Loyalty\Services\LoyaltyService;

class AccountController extends Controller
{
    public function show(Request $request, LoyaltyService $loyalty): Response
    {
        $user = $request->user()->load([
            'addresses',
            'wishlists.product.images',
            'orders.items',
            'orders.user',
        ]);

        $account = $loyalty->accountForUser($user);
        $loyaltyProps = $account ? $loyalty->sharedProps($user) : [];
        $vouchers = $user->company?->vouchers()->whereNull('used_at')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderBy('expires_at')->get() ?? collect();

        return Inertia::render('Catalog::DashboardPage', [
            'account' => [
                'addresses' => $user->addresses->map(fn (Address $address): array => $this->serializeAddress($address))->values()->all(),
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
                'reviews' => [],
                'points_balance' => $loyaltyProps['points_balance'] ?? 0,
                'tier_points' => $loyaltyProps['tier_points'] ?? 0,
                'current_tier' => $loyaltyProps['current_tier'] ?? 'classic',
                ...$loyaltyProps,
                'member_since' => $user->created_at?->format('Y'),
                'reviewsPending' => 0,
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
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('success', 'Alamat berhasil disimpan.');
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
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'string', 'max:10'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
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
            'city' => $address->city,
            'address' => $address->address,
            'postalCode' => $address->postal_code,
            'isDefault' => $address->is_default,
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
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'sku' => $item->sku,
                'qty' => (int) $item->quantity,
                'price' => (int) $item->unit_price,
                'weight' => null,
            ])->values()->all(),
        ];
    }
}
