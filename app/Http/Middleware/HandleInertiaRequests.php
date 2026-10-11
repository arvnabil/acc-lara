<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Modules\Catalog\Services\CatalogQuery;
use Modules\Cart\Models\Cart;
use Modules\Loyalty\Services\LoyaltyService;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user()?->load('company');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'company' => $user->company ? [
                        'id' => $user->company->id,
                        'name' => $user->company->name,
                        'npwp' => $user->company->npwp,
                    ] : null,
                    'company_role' => $user->company_role,
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
        ];
    }
}
