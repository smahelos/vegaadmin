<?php

namespace App\Providers;

use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface;
use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Application\Product\Contracts\ProductRepositoryInterface;
use App\Application\Product\Mappers\ProductArrayMapper;
use App\Application\Product\Services\ProductApplicationService;
use App\Application\Product\Services\ProductAuthorizationService;
use App\Application\Product\Services\ProductInputValidationService;
use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\Product\Contracts\InvoiceProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Domain\Product\Services\ProductService;
use App\Domain\Product\Validation\ProductCreationValidator;
use App\Infrastructure\Authorization\Policies\Product\ProductPolicy;
use App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface;
use App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductCategoryObserver;
use App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductObserver;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentCatalogProductRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductRepository;
use App\Infrastructure\Services\Product\ProductFormDataService;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Gate;
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
        $this->registerApplicationServices();
    }

    /**
     * Domain services and domain-level factories.
     */
    private function registerDomainServices(): void
    {
        $this->app->bind(
            ProductServiceInterface::class,
            ProductService::class,
        );

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

        // Product repositories
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class,
        );

        $this->app->bind(
            ProductDtoReadRepositoryInterface::class,
            EloquentProductDtoRepository::class,
        );

        $this->app->bind(
            ProductDtoWriteRepositoryInterface::class,
            EloquentProductDtoRepository::class,
        );

        $this->app->bind(
            InvoiceProductDtoReadRepositoryInterface::class,
            EloquentInvoiceProductDtoRepository::class,
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

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            ProductApplicationServiceInterface::class,
            ProductApplicationService::class,
        );

        // Application layer services
        $this->app->bind(
            ProductAuthorizationService::class,
        );

        $this->app->bind(
            ProductInputValidationService::class,
        );

        $this->app->bind(
            ProductArrayMapper::class,
        );
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        ProductCategory::observe(ProductCategoryObserver::class);

        Gate::policy(Product::class, ProductPolicy::class);
    }
}
