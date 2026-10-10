<?php

namespace App\Support\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Basis provider modul. Modul cukup menimpa $moduleName
 * dan (opsional) register()/bootModule().
 */
abstract class BaseModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName;

    protected function modulePath(string $path = ''): string
    {
        $base = dirname((new ReflectionClass($this))->getFileName());

        return $path === '' ? $base : $base.'/'.ltrim($path, '/');
    }

    public function boot(): void
    {
        if (is_dir($this->modulePath('Database/Migrations'))) {
            $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
        }

        if (is_file($this->modulePath('Routes/web.php'))) {
            Route::middleware('web')->group($this->modulePath('Routes/web.php'));
        }

        if (is_file($this->modulePath('Routes/api.php'))) {
            Route::middleware('api')->prefix('api')->group($this->modulePath('Routes/api.php'));
        }

        if (is_dir($this->modulePath('lang'))) {
            $this->loadTranslationsFrom($this->modulePath('lang'), strtolower($this->moduleName));
        }

        $this->bootModule();
    }

    /** Hook untuk modul (event listener, jadwal, policy, dll). */
    protected function bootModule(): void {}
}
