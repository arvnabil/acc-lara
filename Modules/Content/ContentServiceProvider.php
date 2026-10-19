<?php

namespace Modules\Content;

use App\Support\Modules\BaseModuleServiceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\NavigationMenu;
use Modules\Content\Models\Banner;

class ContentServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Content';

    protected function bootModule(): void
    {
        Inertia::share('navigationMenus', static fn (): array => NavigationMenu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'label', 'target_type', 'target_value', 'sort_order'])
            ->map(fn (NavigationMenu $menu): array => $menu->toArray())
            ->values()
            ->all());

        Inertia::share('banners', static function (Request $request): array {
            if (! $request->routeIs('home')) {
                return [];
            }

            $now = now();

            return Banner::query()
                ->where('is_active', true)
                ->where(function ($query) use ($now): void {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($query) use ($now): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'title', 'image_url', 'mobile_image_url', 'target_url'])
                ->map(fn (Banner $banner): array => $banner->toArray())
                ->values()
                ->all();
        });
    }
}
