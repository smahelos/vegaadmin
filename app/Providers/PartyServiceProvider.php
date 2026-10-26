<?php

namespace App\Providers;

use App\Application\Party\Contracts\ClientReadRepositoryInterface;
use App\Application\Party\Contracts\ClientWriteRepositoryInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\Party\Contracts\SupplierReadRepositoryInterface;
use App\Application\Party\Contracts\SupplierWriteRepositoryInterface;
use App\Application\Party\Mappers\ClientArrayMapper;
use App\Application\Party\Mappers\SupplierArrayMapper;
use App\Application\Party\Services\PartyApplicationService;
use App\Application\Party\Services\PartyAuthorizationService;
use App\Application\Party\Services\PartyInputValidationService;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\ClientDtoWriteRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Party\Contracts\PartyCreationValidatorInterface;
use App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\SupplierDtoWriteRepositoryInterface;
use App\Domain\Party\Services\InvoicePartyService;
use App\Domain\Party\Validation\PartyCreationValidator;
use App\Infrastructure\Persistence\Eloquent\Party\Observers\ClientObserver;
use App\Infrastructure\Persistence\Eloquent\Party\Observers\SupplierObserver;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentBankAccountRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentBusinessRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentClientDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentClientRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentContactRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentSupplierDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentSupplierRepository;
use App\Models\Client;
use App\Models\Supplier;
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
            InvoicePartyServiceInterface::class,
            InvoicePartyService::class,
        );

        $this->app->bind(
            PartyCreationValidatorInterface::class,
            PartyCreationValidator::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        $this->app->bind(
            BusinessReadRepositoryInterface::class,
            EloquentBusinessRepository::class,
        );

        $this->app->bind(
            BusinessWriteRepositoryInterface::class,
            EloquentBusinessRepository::class,
        );

        $this->app->bind(
            ContactReadRepositoryInterface::class,
            EloquentContactRepository::class,
        );

        $this->app->bind(
            ContactWriteRepositoryInterface::class,
            EloquentContactRepository::class,
        );

        $this->app->bind(
            BankAccountReadRepositoryInterface::class,
            EloquentBankAccountRepository::class,
        );

        $this->app->bind(
            BankAccountWriteRepositoryInterface::class,
            EloquentBankAccountRepository::class,
        );

        // Party repositories (Client & Supplier)
        $this->app->bind(
            ClientReadRepositoryInterface::class,
            EloquentClientRepository::class,
        );

        $this->app->bind(
            ClientWriteRepositoryInterface::class,
            EloquentClientRepository::class,
        );

        $this->app->bind(
            ClientDtoReadRepositoryInterface::class,
            EloquentClientDtoRepository::class,
        );

        $this->app->bind(
            ClientDtoWriteRepositoryInterface::class,
            EloquentClientDtoRepository::class,
        );

        $this->app->bind(
            SupplierReadRepositoryInterface::class,
            EloquentSupplierRepository::class,
        );

        $this->app->bind(
            SupplierWriteRepositoryInterface::class,
            EloquentSupplierRepository::class,
        );

        $this->app->bind(
            SupplierDtoReadRepositoryInterface::class,
            EloquentSupplierDtoRepository::class,
        );

        $this->app->bind(
            SupplierDtoWriteRepositoryInterface::class,
            EloquentSupplierDtoRepository::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        $this->app->bind(
            PartyApplicationServiceInterface::class,
            PartyApplicationService::class,
        );

        // Application layer support services
        $this->app->singleton(
            PartyAuthorizationService::class,
        );

        $this->app->singleton(
            ClientArrayMapper::class,
        );

        $this->app->singleton(
            SupplierArrayMapper::class,
        );

        $this->app->singleton(
            PartyInputValidationService::class,
        );
    }

    public function boot(): void
    {
        Client::observe(ClientObserver::class);
        Supplier::observe(SupplierObserver::class);
    }
}
