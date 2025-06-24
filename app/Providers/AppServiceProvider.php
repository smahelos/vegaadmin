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
use App\Models\Client;
use App\Observers\ClientObserver;
use App\Models\Supplier;
use App\Observers\SupplierObserver;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Models\ProductCategory;
use App\Observers\CategoryObserver;
use App\Models\Tax;
use App\Observers\TaxObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     */
    public function register(): void
    {
        // Load Backpack helpers
        require_once app_path('Helpers/BackpackHelpers.php');

        // Bind repository interfaces to their implementations
        $this->app->bind(
            \App\Contracts\ClientRepositoryInterface::class,
            \App\Repositories\ClientRepository::class
        );
        
        $this->app->bind(
            \App\Contracts\SupplierRepositoryInterface::class,
            \App\Repositories\SupplierRepository::class
        );

        // Bind service interfaces to their implementations
        $this->app->bind(
            \App\Contracts\InvoiceServiceInterface::class,
            \App\Services\InvoiceService::class
        );
        
        $this->app->bind(
            \App\Contracts\InvoicePdfServiceInterface::class,
            \App\Services\InvoicePdfService::class
        );
        
        $this->app->bind(
            \App\Contracts\LocaleServiceInterface::class,
            \App\Services\LocaleService::class
        );
        
        $this->app->bind(
            \App\Contracts\QrPaymentServiceInterface::class,
            \App\Services\QrPaymentService::class
        );

        // Bind additional service interfaces (Fáze 3)
        $this->app->bind(
            \App\Contracts\BankServiceInterface::class,
            \App\Services\BankService::class
        );
        
        $this->app->bind(
            \App\Contracts\ProductsServiceInterface::class,
            \App\Services\ProductsService::class
        );
        
        $this->app->bind(
            \App\Contracts\StatusServiceInterface::class,
            \App\Services\StatusService::class
        );
        
        $this->app->bind(
            \App\Contracts\TaxesServiceInterface::class,
            \App\Services\TaxesService::class
        );
        
        $this->app->bind(
            \App\Contracts\FileUploadServiceInterface::class,
            \App\Services\FileUploadService::class
        );
        
        $this->app->bind(
            \App\Contracts\InvoiceProductSyncServiceInterface::class,
            \App\Services\InvoiceProductSyncService::class
        );

        // Utility Services Interfaces (Phase 4)
        $this->app->bind(
            \App\Contracts\CountryServiceInterface::class,
            \App\Services\CountryService::class
        );
        
        $this->app->bind(
            \App\Contracts\CurrencyServiceInterface::class,
            \App\Services\CurrencyService::class
        );
        
        $this->app->bind(
            \App\Contracts\CurrencyExchangeServiceInterface::class,
            \App\Services\CurrencyExchangeService::class
        );
        
        $this->app->bind(
            \App\Contracts\ArtisanCommandsServiceInterface::class,
            \App\Services\ArtisanCommandsService::class
        );

        // Dashboard Service Interface (Phase 2)
        $this->app->bind(
            \App\Contracts\DashboardServiceInterface::class,
            \App\Services\DashboardService::class
        );

        // Product Service and Repository Interfaces (Phase 2)
        $this->app->bind(
            \App\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\ProductRepository::class
        );
        
        $this->app->bind(
            \App\Contracts\ProductServiceInterface::class,
            \App\Services\ProductService::class
        );

        // User Service Interface (Phase 2)
        $this->app->bind(
            \App\Contracts\UserServiceInterface::class,
            \App\Services\UserService::class
        );

        // Cache Service Interface (Phase 3)
        $this->app->bind(
            \App\Contracts\CacheServiceInterface::class,
            \App\Services\CacheService::class
        );

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
        // Observe model events for cache invalidation
        // Invoice observer handles product sync and cache invalidation for user dashboard stats
        Invoice::observe(InvoiceObserver::class);
        
        // Client observer handles cache invalidation for client-related dashboard stats
        Client::observe(ClientObserver::class);
        
        // Supplier observer handles cache invalidation for supplier-related dashboard stats
        Supplier::observe(SupplierObserver::class);
        
        // Product observer handles cache invalidation for form data (product lists)
        Product::observe(ProductObserver::class);
        
        // ProductCategory observer handles cache invalidation for form data (category lists)
        ProductCategory::observe(CategoryObserver::class);
        
        // Tax observer handles cache invalidation for form data (tax lists)
        Tax::observe(TaxObserver::class);

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
