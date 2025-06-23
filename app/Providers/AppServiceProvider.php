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
use App\Models\Invoice;
use App\Observers\InvoiceObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     */
    public function register(): void
    {
        // Load Backpack helpers
        require_once app_path('Helpers/BackpackHelpers.php');

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
        // Observe Invoice model events
        // This observer will handle the creation and update events for the Invoice model
        // and synchronize products from the invoice_text JSON to the pivot table
        // The observer will also handle the bulk update of all invoices if needed
        Invoice::observe(InvoiceObserver::class);

        // Add Blade directive for checking users with admin access
        Blade::if('backpackUser', function () {
            return UserHelpers::isBackpackUser();
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
