<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Modules\Clients\Models\Address;
use Modules\Catalog\Services\CatalogQuery;
use Modules\Cart\Models\Cart;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\WhatsApp\Models\WhatsAppSetting;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user()?->load(['client', 'addresses']);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'client' => $user->client ? [
                        'id' => $user->client->id,
                        'name' => $user->client->name,
                        'npwp' => $user->client->npwp,
                    ] : null,
                    'company_role' => $user->company_role,
                    'referral_code' => $user->referral_code,
                    'addresses' => $user->addresses->map(fn (Address $address): array => [
                        'id' => $address->id,
                        'label' => $address->label,
                        'recipientName' => $address->recipient_name,
                        'phone' => $address->phone,
                        'province' => $address->province,
                        'city' => $address->city,
                        'address' => $address->address,
                        'postalCode' => $address->postal_code,
                        'isDefault' => $address->is_default,
                    ])->values()->all(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'categoryMenu' => fn (): array => Cache::remember(
                'catalog.category-menu',
                now()->addMinutes(10),
                fn (): array => app(CatalogQuery::class)->categories(),
            ),
            'cart' => fn (): array => $user ? ['count' => Cart::query()->where('user_id', $user->id)->withCount('items')->first()?->items_count ?? 0] : ['count' => 0],
            'wishlist' => fn (): array => $user ? ['ids' => $user->wishlists()->pluck('product_id')->values()->all()] : ['ids' => []],
            'loyalty' => fn (): ?array => app(LoyaltyService::class)->sharedProps($user),
            'whatsapp' => fn (): array => WhatsAppSetting::getInstance()->only([
                'is_enabled', 'phone', 'message', 'tooltip', 'position', 'show_online_badge',
                'show_pulse_animation', 'open_in_new_tab', 'offset_bottom', 'offset_side',
                'icon', 'button_color', 'active_pages',
            ]),
        ];
    }
}




