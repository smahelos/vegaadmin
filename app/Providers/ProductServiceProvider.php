<?php

namespace App\Providers;

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
            \App\Domain\Product\Contracts\ProductServiceInterface::class,
            \App\Domain\Product\Services\ProductService::class
        );

        $this->app->bind(
            \App\Domain\Product\Validation\ProductCreationValidator::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Product repositories
        $this->app->bind(
            \App\Application\Product\Contracts\ProductRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductRepository::class
        );

        $this->app->bind(
            \App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentProductDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Product\Contracts\InvoiceProductDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductDtoRepository::class
        );

        // Application layer product repositories
        $this->app->bind(
            \App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductRepository::class
        );

        $this->app->bind(
            \App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Product\Repositories\EloquentInvoiceProductRepository::class
        );

        // Infrastructure product form data service
        $this->app->bind(
            \App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface::class,
            \App\Infrastructure\Services\Product\ProductFormDataService::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\Product\Contracts\ProductApplicationServiceInterface::class,
            \App\Application\Product\Services\ProductApplicationService::class
        );

        // Application layer services
        $this->app->bind(
            \App\Application\Product\Services\ProductAuthorizationService::class
        );

        $this->app->bind(
            \App\Application\Product\Services\ProductInputValidationService::class
        );

        $this->app->bind(
            \App\Application\Product\Mappers\ProductArrayMapper::class
        );
    }

    public function boot(): void
    {
        \App\Models\Product::observe(\App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductObserver::class);
        \App\Models\ProductCategory::observe(\App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductCategoryObserver::class);

        Gate::policy(\App\Models\Product::class, \App\Infrastructure\Authorization\Policies\Product\ProductPolicy::class);
    }
}
