<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Invoice bounded context.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class InvoiceServiceProvider extends ServiceProvider
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
            \App\Domain\Invoice\Contracts\InvoiceServiceInterface::class,
            \App\Domain\Invoice\Services\InvoiceService::class
        );

        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface::class,
            \App\Domain\Invoice\Services\InvoicePrintDataBuilder::class
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Invoice reminders notifier
        $this->app->bind(
            \App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface::class,
            \App\Infrastructure\Notifications\Invoice\LaravelInvoiceReminderNotifier::class
        );

        // Invoice repositories
        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceRepository::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceRepository::class
        );

        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceDtoRepository::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        // Invoice application services
        $this->app->bind(
            \App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface::class,
            \App\Application\Invoice\Services\GuestInvoiceApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface::class,
            \App\Application\Invoice\Services\InvoiceFrontendActionsApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface::class,
            \App\Application\Invoice\Services\InvoiceRequestAssembler::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface::class,
            \App\Application\Invoice\Services\InvoiceMutationApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface::class,
            \App\Application\Invoice\Services\InvoicePdfApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoicePdfRendererInterface::class,
            \App\Application\Invoice\Services\InvoicePdfRenderer::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface::class,
            \App\Application\Invoice\Services\TemporaryInvoiceStore::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface::class,
            \App\Application\Invoice\Services\TemporaryInvoicePrintDataFactory::class
        );

        // Specialized Invoice Application Services
        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface::class,
            \App\Application\Invoice\Services\Specialized\InvoiceFormApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface::class,
            \App\Application\Invoice\Services\Specialized\InvoiceCrudApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface::class,
            \App\Application\Invoice\Services\Specialized\InvoiceListingApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceStatusApplicationServiceInterface::class,
            \App\Application\Invoice\Services\Specialized\InvoiceStatusApplicationService::class
        );

        $this->app->bind(
            \App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface::class,
            \App\Application\Invoice\Services\Specialized\InvoiceLimitApplicationService::class
        );
    }

    public function boot(): void
    {
        \App\Models\Invoice::observe(\App\Infrastructure\Persistence\Eloquent\Invoice\Observers\InvoiceObserver::class);
        \App\Models\Tax::observe(\App\Infrastructure\Persistence\Eloquent\Invoice\Observers\TaxObserver::class);
    }
}
