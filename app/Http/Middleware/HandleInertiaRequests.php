<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Modules\Catalog\Services\CatalogQuery;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user(),
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
        ];
    }
}
