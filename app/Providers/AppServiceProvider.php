<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use App\View\Components\ApplicationLogo;
use App\View\Components\NavLink;
use App\View\Components\ResponsiveNavLink;
use App\View\Components\Dropdown;
use App\View\Components\DropdownLink;
use App\View\Components\Select;
use App\View\Components\CurrencySelect;
use App\View\Components\Pagination;

// Infrastructure services and repositories bindings moved to app/Infrastructure/InfrastructureServiceProvider
// Observers registrations moved to app/Infrastructure/InfrastructureServiceProvider
// Application service bindings moved to app/Application/ApplicationServiceProvider

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     */
    public function register(): void
    {
        // Load Backpack helpers
        require_once app_path('Helpers/BackpackHelpers.php');

        // Bind PermissionManager's UserCrudController to our custom controller
        // This allows us to override the default user management functionality
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
        // Model observers moved to their respective domain service providers
        // Add Blade directive for checking users with admin access
        Blade::if('backpackUser', function () {
            return Auth::check() && function_exists('backpack_user') && backpack_user() !== null;
        });

        // Set default pagination views
        \Illuminate\Pagination\Paginator::defaultView('components.pagination');
        \Illuminate\Pagination\Paginator::defaultSimpleView('components.simple-pagination');

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
