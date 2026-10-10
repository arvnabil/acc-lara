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
        // Daftarkan semua permission dari module.json sebagai Gate ability (native, tanpa Spatie).
        foreach ($this->app->make(ModuleManager::class)->enabled() as $module) {
            foreach ($module->permissions as $permission) {
                Gate::define($permission, fn ($user) => $user->hasPermission($permission));
            }
        }

        // Administrator melewati semua pengecekan.
        Gate::before(fn ($user) => $user->isAdministrator() ? true : null);
    }
}
