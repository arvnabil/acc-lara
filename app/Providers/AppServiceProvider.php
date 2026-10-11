<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Modules\Cart\Listeners\MergeGuestCartAfterLogin;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, MergeGuestCartAfterLogin::class);
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower((string) $request->string('email')).'|'.$request->ip());
        });

        RateLimiter::for('password.email', function (Request $request): Limit {
            return Limit::perMinute(3)->by(Str::lower((string) $request->string('email')).'|'.$request->ip());
        });

        // Implicitly grant 'administrator' role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('administrator') ? true : null;
        });
    }
}
