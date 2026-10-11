<?php

namespace Modules\Accounts\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounts\Models\Address;

class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user()->load(['addresses', 'wishlists.product.images']);

        return Inertia::render('Catalog::DashboardPage', [
            'account' => [
                'addresses' => $user->addresses->map(fn (Address $address): array => $this->serializeAddress($address))->values()->all(),
                'orders' => [],
                'vouchers' => [],
                'wishlist' => $user->wishlists->pluck('product_id')->values()->all(),
                'wishlistProducts' => $user->wishlists->pluck('product')->filter()->values()->all(),
                'reviews' => [],
                'points_balance' => 0,
                'tier_points' => 0,
                'current_tier' => 'Classic',
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
}
