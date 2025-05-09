<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\UserHelpers;
use App\View\Components\ApplicationLogo;
use App\View\Components\NavLink;
use App\View\Components\ResponsiveNavLink;
use App\View\Components\Dropdown;
use App\View\Components\DropdownLink;
use App\View\Components\Select;
use App\View\Components\CurrencySelect;
use App\View\Components\Pagination;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     */
    public function register(): void
    {
        // Load Backpack helpers
        require_once app_path('Helpers/BackpackHelpers.php');

        // Register localized route helper function
        require_once app_path('Helpers/LocaleHelper.php');

        $this->app->bind(
            \Backpack\PermissionManager\app\Http\Controllers\UserCrudController::class,
            \App\Http\Controllers\Admin\UserCrudController::class
        );
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // Add Blade directive for checking users with admin access
        Blade::if('backpackUser', function () {
            return UserHelpers::isBackpackUser();
        });

        // Create custom directive for generating URLs with language parameter
        Blade::directive('localizedRoute', function ($expression) {
            $parts = explode(',', $expression, 2);
            $route = trim($parts[0]);

            if (count($parts) === 1) {
                return "<?php echo route({$route}, ['lang' => app()->getLocale()]); ?>";
            } else {
                $params = trim($parts[1]);
                return "<?php 
                    \$params = {$params};
                    if (is_array(\$params)) {
                        \$params['lang'] = app()->getLocale();
                        echo route({$route}, \$params);
                    } elseif (is_numeric(\$params)) {
                        echo route({$route}, ['id' => \$params, 'lang' => app()->getLocale()]);
                    } else {
                        echo route({$route}, ['id' => \$params, 'lang' => app()->getLocale()]);
                    }
                ?>";
            }
        });

        // Register custom Blade components
        Blade::component('application-logo', ApplicationLogo::class);
        Blade::component('nav-link', NavLink::class);
        Blade::component('responsive-nav-link', ResponsiveNavLink::class);
        Blade::component('dropdown', Dropdown::class);
        Blade::component('dropdown-link', DropdownLink::class);
        Blade::component('select', Select::class);
        Blade::component('currency-select', CurrencySelect::class);
        Blade::component('pagination', Pagination::class);
    }
}
