<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Product;
use App\Infrastructure\Authorization\Policies\Product\ProductPolicy;

/**
 * Consolidated Domain Service Provider.
 * 
 * Registers all Domain layer services, value objects, validators, and business logic
 * across all bounded contexts (Analytics, Invoice, Party, Product, User, Content, Shared).
 * 
 * This provider is framework-agnostic and contains only pure domain bindings.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSharedDomainServices();
        $this->registerAnalyticsDomainServices();
        $this->registerInvoiceDomainServices();
        $this->registerPartyDomainServices();
        $this->registerProductDomainServices();
        $this->registerUserDomainServices();
        $this->registerContentDomainServices();
        $this->registerPaymentDomainServices();
    }

    /**
     * Register Shared domain services (cross-cutting concerns).
     */
    private function registerSharedDomainServices(): void
    {
        // Status service (used by multiple domains)
        $this->app->bind(
            \App\Domain\Shared\Status\Contracts\StatusServiceInterface::class,
            \App\Domain\Shared\Status\Services\StatusService::class
        );

        // File Upload Config wrapper (singleton)
        $this->app->singleton(\App\Domain\Shared\File\Config\FileUploadConfig::class, function () {
            return new \App\Domain\Shared\File\Config\FileUploadConfig();
        });

        // Money & Currency services
        if (config('exchange.deterministic_enabled')) {
            $this->app->bind(
                \App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface::class,
                function () {
                    $raw = config('exchange.deterministic_rates');
                    $rates = null;
                    if (is_string($raw) && $raw !== '') {
                        $parsed = [];
                        foreach (explode(',', $raw) as $pair) {
                            [$c, $v] = array_pad(explode(':', $pair, 2), 2, null);
                            if ($c && $v && is_numeric($v)) { $parsed[$c] = (float)$v; }
                        }
                        if ($parsed) { $rates = $parsed; }
                    }
                    return new \App\Domain\Shared\Money\Services\DeterministicCurrencyExchangeService($rates);
                }
            );
        } else {
            $this->app->bind(
                \App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface::class,
                \App\Domain\Shared\Money\Services\CurrencyExchangeService::class
            );
        }

        $this->app->bind(
            \App\Domain\Shared\Money\Contracts\CurrencyServiceInterface::class,
            \App\Domain\Shared\Money\Services\CurrencyService::class
        );

        $this->app->bind(
            \App\Domain\Shared\Money\Contracts\MoneyFormatterInterface::class,
            \App\Domain\Shared\Money\Services\MoneyFormatter::class
        );

        // Geography services
        $this->app->bind(
            \App\Domain\Shared\Geography\Contracts\CountryServiceInterface::class,
            \App\Domain\Shared\Geography\Services\CountryService::class
        );

        // Console / Artisan Commands meta service
        $this->app->bind(
            \App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface::class,
            \App\Domain\Shared\Console\Services\ArtisanCommandsService::class
        );

        // Tax service
        $this->app->bind(
            \App\Domain\Shared\Tax\Contracts\TaxServiceInterface::class,
            \App\Domain\Shared\Tax\Services\TaxService::class
        );
    }

    /**
     * Register Analytics domain services.
     */
    private function registerAnalyticsDomainServices(): void
    {
        $this->app->bind(
            \App\Domain\Analytics\Contracts\DashboardServiceInterface::class,
            \App\Domain\Analytics\Services\DashboardService::class
        );
    }

    /**
     * Register Invoice domain services.
     */
    private function registerInvoiceDomainServices(): void
    {
        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoiceServiceInterface::class,
            \App\Domain\Invoice\Services\InvoiceService::class
        );

        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface::class,
            \App\Domain\Invoice\Services\InvoicePrintDataBuilder::class
        );

        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoiceProductSyncServiceInterface::class,
            \App\Domain\Invoice\Services\InvoiceProductSyncService::class
        );
    }

    /**
     * Register Party domain services (Client & Supplier).
     */
    private function registerPartyDomainServices(): void
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
     * Register Product domain services.
     */
    private function registerProductDomainServices(): void
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
     * Register User domain services.
     */
    private function registerUserDomainServices(): void
    {
        $this->app->bind(
            \App\Domain\User\Contracts\LocaleServiceInterface::class,
            \App\Domain\User\Services\LocaleService::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\UserServiceInterface::class,
            \App\Domain\User\Services\UserService::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\PermissionLimitResolverInterface::class,
            \App\Domain\User\Services\PermissionLimitResolver::class
        );

        $this->app->bind(
            \App\Domain\User\Contracts\UniversalLimitServiceInterface::class,
            \App\Domain\User\Services\UniversalLimitService::class
        );
    }

    /**
     * Register Content domain services.
     */
    private function registerContentDomainServices(): void
    {
        $this->app->bind(
            \App\Domain\Content\Contracts\PageServiceInterface::class,
            \App\Domain\Content\Services\PageService::class
        );
    }

    /**
     * Register Payment domain services.
     */
    private function registerPaymentDomainServices(): void
    {
        // Intentionally left minimal: payment bindings are moved to PaymentDomainServiceProvider
    }

    public function boot(): void
    {
        // Register Product policy mapping with Gate at domain level
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
