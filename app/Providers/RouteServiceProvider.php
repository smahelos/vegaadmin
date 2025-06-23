<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/cs/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Register macro for localized routes
        $this->registerLocalizedRouteMacro();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register macro for generating localized routes
     */
    protected function registerLocalizedRouteMacro(): void
    {
        Route::macro('localized', function ($group) {
            $availableLocales = config('app.available_locales', ['cs', 'en', 'de', 'sk']);
            $supportedLocales = implode('|', $availableLocales);

            Route::group([], $group);

            Route::group(['prefix' => '{locale}', 'where'=> ['locale' => $supportedLocales]], $group);
            // foreach ($availableLocales as $locale) {
            //     $localizedUri = $locale . '/' . ltrim($uri, '/');
            //     $localizedName = $locale . '.' . $name;
                
            //     Route::get($localizedUri, $action)
            //         ->name($localizedName)
            //         ->where('locale', $locale);
            // }
        });
    }
}
