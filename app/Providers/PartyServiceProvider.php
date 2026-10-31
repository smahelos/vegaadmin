<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\Contracts\CompanyRegistryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Infrastructure\Gateways\Party\AresCompanyRegistry;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentBankAccountRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentBusinessRepository;
use App\Infrastructure\Persistence\Eloquent\Party\Repositories\EloquentContactRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Party (businesses and contacts) bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
final class PartyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerInfrastructure();
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

        $this->app->bind(
            CompanyRegistryInterface::class,
            AresCompanyRegistry::class,
        );

    }
}
