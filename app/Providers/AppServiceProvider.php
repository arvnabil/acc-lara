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
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;

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
        Model::preventLazyLoading(! app()->isProduction());

        if (app()->isProduction() || Str::startsWith((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Event::listen(Login::class, MergeGuestCartAfterLogin::class);
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower((string) $request->string('email')).'|'.$request->ip());
        });

        RateLimiter::for('password.email', function (Request $request): Limit {
            return Limit::perMinute(3)->by(Str::lower((string) $request->string('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('loyalty.redeem', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->getAuthIdentifier()));
        RateLimiter::for('reviews', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->getAuthIdentifier()));
        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->getAuthIdentifier()));
        RateLimiter::for('cart', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->getAuthIdentifier().'|'.$request->ip()));
        RateLimiter::for('wishlist', fn (Request $request): Limit => Limit::perMinute(60)->by((string) $request->user()?->getAuthIdentifier()));

        // Implicitly grant 'administrator' role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('administrator') ? true : null;
        });
    }
}
