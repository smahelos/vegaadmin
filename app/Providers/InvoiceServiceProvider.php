<?php

namespace App\Providers;

use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface;
use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\Services\InvoiceRequestAssembler;
use App\Application\Invoice\Services\TemporaryInvoicePrintDataFactory;
use App\Application\Invoice\Services\TemporaryInvoiceStore;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\Contracts\InvoiceReminderReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface;
use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Invoice\Services\InvoiceService;
use App\Infrastructure\Notifications\Invoice\LaravelInvoiceReminderNotifier;
use App\Infrastructure\Persistence\Eloquent\Invoice\Observers\InvoiceObserver;
use App\Infrastructure\Persistence\Eloquent\Invoice\Observers\TaxObserver;
use App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceListRepository;
use App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceReminderRepository;
use App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceRepository;
use App\Infrastructure\Renderers\Invoice\InvoicePdfRenderer;
use App\Models\Invoice;
use App\Models\Tax;
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
            InvoiceServiceInterface::class,
            InvoiceService::class,
        );

        $this->app->bind(
            InvoicePrintDataBuilderInterface::class,
            InvoicePrintDataBuilder::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        $this->app->bind(InvoiceListReadRepositoryInterface::class, EloquentInvoiceListRepository::class);
        $this->app->bind(InvoiceReminderReadRepositoryInterface::class, EloquentInvoiceReminderRepository::class);

        // Invoice reminders notifier
        $this->app->bind(
            InvoiceReminderNotifierInterface::class,
            LaravelInvoiceReminderNotifier::class,
        );

        // Invoice repositories
        $this->app->bind(
            InvoiceReadRepositoryInterface::class,
            EloquentInvoiceRepository::class,
        );

        $this->app->bind(
            InvoiceWriteRepositoryInterface::class,
            EloquentInvoiceRepository::class,
        );

        $this->app->bind(
            InvoiceDtoReadRepositoryInterface::class,
            EloquentInvoiceDtoRepository::class,
        );

        $this->app->bind(
            InvoiceDtoWriteRepositoryInterface::class,
            EloquentInvoiceDtoRepository::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        // Invoice application services
        $this->app->bind(
            InvoiceRequestAssemblerInterface::class,
            InvoiceRequestAssembler::class,
        );

        $this->app->bind(
            InvoicePdfRendererInterface::class,
            InvoicePdfRenderer::class,
        );

        $this->app->bind(
            TemporaryInvoiceStoreInterface::class,
            TemporaryInvoiceStore::class,
        );

        $this->app->bind(
            TemporaryInvoicePrintDataFactoryInterface::class,
            TemporaryInvoicePrintDataFactory::class,
        );
    }

    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        Tax::observe(TaxObserver::class);
    }
}
