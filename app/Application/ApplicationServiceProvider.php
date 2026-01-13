<?php

namespace App\Application;

use Illuminate\Support\ServiceProvider;

/**
 * Application layer service provider.
 *
 * Registers bindings for Application use-case services to keep
 * controllers decoupled from Domain and Infrastructure.
 */
class ApplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSharedApplicationServices();
        $this->registerAnalyticsApplicationServices();
        $this->registerInvoiceApplicationServices();
        $this->registerPartyApplicationServices();
        $this->registerProductApplicationServices();
        $this->registerUserApplicationServices();
        $this->registerContentApplicationServices();
        $this->registerPaymentApplicationServices();
    }

    /**
     * Register shared Application layer services.
     */
    private function registerSharedApplicationServices(): void
    {
        // Money (Currency) application service binding
        $this->app->bind(
            \App\Application\Shared\Money\Contracts\CurrencyApplicationServiceInterface::class,
            \App\Application\Shared\Money\Services\CurrencyApplicationService::class
        );

        // Geography (Country) application service binding
        $this->app->bind(
            \App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface::class,
            \App\Application\Shared\Geography\Services\CountryApplicationService::class
        );

        // Locale application service binding
        $this->app->bind(
            \App\Application\Shared\Geography\Contracts\LocaleApplicationServiceInterface::class,
            \App\Application\Shared\Geography\Services\LocaleApplicationService::class
        );

        // Generic Authorization service binding for application layer
        $this->app->bind(
            \App\Application\Auth\Contracts\AuthorizationServiceInterface::class,
            \App\Application\Auth\Services\AuthorizationService::class
        );
    }

    /**
     * Register Analytics Application services.
     */
    private function registerAnalyticsApplicationServices(): void
    {
        // Analytics application services
        $this->app->bind(
            \App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface::class,
            \App\Application\Analytics\Services\ApiStatisticsApplicationService::class
        );
        $this->app->bind(
            \App\Application\Analytics\Contracts\DashboardApplicationServiceInterface::class,
            \App\Application\Analytics\Services\DashboardApplicationService::class
        );

        // Analytics presenters
        $this->app->bind(
            \App\Application\Analytics\Contracts\UserStatisticsPresenterInterface::class,
            \App\Application\Analytics\Presenters\UserStatisticsPresenter::class
        );
        $this->app->bind(
            \App\Application\Analytics\Contracts\MonthlyStatPresenterInterface::class,
            \App\Application\Analytics\Presenters\MonthlyStatPresenter::class
        );
        $this->app->bind(
            \App\Application\Analytics\Contracts\ClientTotalPresenterInterface::class,
            \App\Application\Analytics\Presenters\ClientTotalPresenter::class
        );
    }

    /**
     * Register Invoice Application services.
     */
    private function registerInvoiceApplicationServices(): void
    {
        // Specialized Invoice Application Services
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

    /**
     * Register Party Application services.
     */
    private function registerPartyApplicationServices(): void
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

    /**
     * Register Product Application services.
     */
    private function registerProductApplicationServices(): void
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

    /**
     * Register User Application services.
     */
    private function registerUserApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\User\Contracts\UserApplicationServiceInterface::class,
            \App\Application\User\Services\UserApplicationService::class
        );
        $this->app->bind(
            \App\Application\User\Contracts\UELSApplicationServiceInterface::class,
            \App\Application\User\Services\UELSApplicationService::class
        );
    }

    /**
     * Register Content Application services.
     */
    private function registerContentApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\Content\Contracts\PageApplicationServiceInterface::class,
            \App\Application\Content\Services\PageApplicationService::class
        );
    }

    /**
     * Register Payment Application services.
     */
    private function registerPaymentApplicationServices(): void
    {
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentApplicationServiceInterface::class,
            \App\Application\Payment\Services\PaymentApplicationService::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\BankApplicationServiceInterface::class,
            \App\Application\Payment\Services\BankApplicationService::class
        );
        
        // Application Payment mappers
        $this->app->singleton(\App\Application\Payment\Mappers\QrPaymentPayloadMapper::class);
    }

    public function boot(): void
    {
        // No boot logic needed for now
    }
}
