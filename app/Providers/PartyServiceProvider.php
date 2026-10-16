<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Party (clients & suppliers) bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class PartyServiceProvider extends ServiceProvider
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
            \App\Domain\Party\Contracts\InvoicePartyServiceInterface::class,
            \App\Domain\Party\Services\InvoicePartyService::class
        );

        $this->app->bind(
            \App\Domain\Party\Contracts\PartyCreationValidatorInterface::class,
            \App\Domain\Party\Validation\PartyCreationValidator::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Party repositories (Client & Supplier)
        $this->app->bind(
            \App\Application\Party\Contracts\ClientReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentClientRepository::class
        );

        $this->app->bind(
            \App\Application\Party\Contracts\ClientWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentClientRepository::class
        );

        $this->app->bind(
            \App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentClientDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Party\Contracts\ClientDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentClientDtoRepository::class
        );

        $this->app->bind(
            \App\Application\Party\Contracts\SupplierReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentSupplierRepository::class
        );

        $this->app->bind(
            \App\Application\Party\Contracts\SupplierWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentSupplierRepository::class
        );

        $this->app->bind(
            \App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentSupplierDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Party\Contracts\SupplierDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentSupplierDtoRepository::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\Party\Contracts\PartyApplicationServiceInterface::class,
            \App\Application\Party\Services\PartyApplicationService::class
        );

        // Application layer support services
        $this->app->singleton(
            \App\Application\Party\Services\PartyAuthorizationService::class
        );

        $this->app->singleton(
            \App\Application\Party\Mappers\ClientArrayMapper::class
        );

        $this->app->singleton(
            \App\Application\Party\Mappers\SupplierArrayMapper::class
        );

        $this->app->singleton(
            \App\Application\Party\Services\PartyInputValidationService::class
        );
    }

    public function boot(): void
    {
        \App\Models\Client::observe(\App\Infrastructure\Persistence\Eloquent\Party\Observers\ClientObserver::class);
        \App\Models\Supplier::observe(\App\Infrastructure\Persistence\Eloquent\Party\Observers\SupplierObserver::class);
    }
}
