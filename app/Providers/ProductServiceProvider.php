<?php

namespace App\Providers;

use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface;
use App\Application\Product\Contracts\ProductFormDataServiceInterface;
use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\Validation\ProductCreationValidator;
use App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductCategoryObserver;
use App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductObserver;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentCatalogProductRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductDtoRepository;
use App\Infrastructure\Services\Product\ProductFormDataService;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Product bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDomainServices();
        $this->registerInfrastructure();
    }

    /**
     * Domain services and domain-level factories.
     */
    private function registerDomainServices(): void
    {
        $this->app->bind(
            ProductCreationValidator::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        $this->app->bind(CatalogProductReadRepositoryInterface::class, EloquentCatalogProductRepository::class);

        $this->app->bind(
            ProductDtoReadRepositoryInterface::class,
            EloquentProductDtoRepository::class,
        );

        $this->app->bind(
            ProductDtoWriteRepositoryInterface::class,
            EloquentProductDtoRepository::class,
        );

        $this->app->bind(
            InvoiceProductDtoWriteRepositoryInterface::class,
            EloquentInvoiceProductDtoRepository::class,
        );

        // Application layer product repositories
        $this->app->bind(
            InvoiceProductReadRepositoryInterface::class,
            EloquentInvoiceProductRepository::class,
        );

        $this->app->bind(
            InvoiceProductWriteRepositoryInterface::class,
            EloquentInvoiceProductRepository::class,
        );

        // Infrastructure product form data service
        $this->app->bind(
            ProductFormDataServiceInterface::class,
            ProductFormDataService::class,
        );
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        ProductCategory::observe(ProductCategoryObserver::class);
    }
}
