<?php

namespace App\Providers;

use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);

        foreach ($this->app->make(ModuleManager::class)->enabled() as $module) {
            if (class_exists($module->providerClass())) {
                $this->app->register($module->providerClass());
            }
        }
    }

    public function boot(): void
    {
        // Role administrator lolos semua lewat Gate::before (Spatie HasRoles).
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('administrator') ? true : null;
        });
    }
}
