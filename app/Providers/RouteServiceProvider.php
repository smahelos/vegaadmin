<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Register macro for localized routes
        $this->registerLocalizedRouteMacro();

        $this->routes(
            function (): void {
                $registrar = Route::middleware('api');
                $registrar->prefix('api');
                $registrar->group(
                    base_path('routes/api.php'),
                );

                $registrar = Route::middleware('web');
                $registrar->group(
                    base_path('routes/web.php'),
                );

                // Load user override routes after other backpack routes
                if (
                    file_exists(
                        base_path('routes/backpack/user-override.php'),
                    )
                ) {
                    $this->loadRoutesFrom(
                        base_path('routes/backpack/user-override.php'),
                    );
                }
            },
        );
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for(
            'api',
            function (Request $request) {
                $limit = Limit::perMinute(60);

                return $limit->by($request->user()?->id ?: $request->ip());
            },
        );

        // Unhashed keys ("create:<user id>") let Livewire actions count against the same bucket (ThrottlesCreation)
        ThrottleRequests::shouldHashKeys(false);
        RateLimiter::for(
            'create',
            function (Request $request): Limit {
                $limit = Limit::perMinute(
                    Config::integer('billex.rate_limits.create'),
                );
                $user = $request->user() ?? $request->user('backpack');

                return $limit->by((string) ($user?->getAuthIdentifier() ?? $request->ip()));
            },
        );
        RateLimiter::for(
            'guest_invoice',
            function (Request $request): Limit {
                $limit = Limit::perMinute(
                    Config::integer('billex.rate_limits.guest_invoice'),
                );

                return $limit->by((string) $request->ip());
            },
        );
    }

    /**
     * Register macro for generating localized routes
     */
    protected function registerLocalizedRouteMacro(): void
    {
        Route::macro(
            'localized',
            function ($group): void {
                $availableLocales = config('app.available_locales', [
                    'cs',
                    'en',
                    'de',
                    'sk',
                ]);
                $supportedLocales = implode('|', $availableLocales);

                // Register routes only with locale prefix to avoid duplicates
                Route::group(
                    ['prefix' => '{locale}', 'where' => ['locale' => $supportedLocales]],
                    $group,
                );
            },
        );
    }
}
