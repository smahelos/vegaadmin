<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the Shared kernel: cross-cutting money, files, time, status, tax
 * and framework adapters used by all bounded contexts.
 *
 * Wires the context's Domain, Infrastructure and Application layers in one place (ADR D-042).
 */
class SharedServiceProvider extends ServiceProvider
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
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Domain events publisher
        $this->app->bind(
            \App\Domain\Shared\Events\Contracts\EventPublisherInterface::class,
            \App\Infrastructure\Shared\Events\LaravelEventPublisher::class
        );

        // File services
        $this->app->bind(
            \App\Domain\Shared\File\Contracts\FileUploadServiceInterface::class,
            \App\Infrastructure\Shared\File\Services\LaravelFileUploadService::class
        );

        $this->app->bind(
            \App\Domain\Shared\File\Contracts\ImageProcessorInterface::class,
            \App\Infrastructure\Shared\File\Processors\GdImageProcessor::class
        );

        // Translation adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Translation\Contracts\TranslatorInterface::class,
            \App\Infrastructure\Shared\Translation\LaravelTranslator::class
        );

        // Cache adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Cache\Contracts\CacheServiceInterface::class,
            \App\Infrastructure\Shared\Cache\CacheService::class
        );

        // Time services adapters
        $this->app->bind(
            \App\Domain\Shared\Time\Contracts\ClockInterface::class,
            \App\Infrastructure\Shared\Time\CarbonClock::class
        );

        $this->app->bind(
            \App\Domain\Shared\Time\Contracts\PeriodServiceInterface::class,
            \App\Infrastructure\Shared\Time\CarbonPeriodService::class
        );

        // Logging adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Log\Contracts\LogInterface::class,
            \App\Infrastructure\Shared\Log\LaravelLogger::class
        );

        // Config adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Config\Contracts\Config::class,
            \App\Infrastructure\Shared\Config\Services\LaravelConfigAdapter::class
        );

        // Session adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Session\Contracts\SessionInterface::class,
            \App\Infrastructure\Shared\Session\LaravelSessionStore::class
        );

        // Str adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Str\Contracts\Str::class,
            \App\Infrastructure\Shared\Str\Services\LaravelStrAdapter::class
        );

        // Hash adapter for Domain
        $this->app->bind(
            \App\Domain\Shared\Hash\Contracts\HashInterface::class,
            \App\Infrastructure\Shared\Hash\LaravelHash::class
        );

        // HTTP Client
        $this->app->bind(
            \App\Domain\Shared\Http\Contracts\HttpClientInterface::class,
            \App\Infrastructure\Shared\Http\Services\HttpClientService::class
        );

        // Transaction boundary
        $this->app->bind(
            \App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Database\EloquentTransactionBoundary::class
        );

        // Shared repositories
        $this->app->bind(
            \App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories\EloquentStatusDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Shared\Status\Contracts\StatusCategoryDtoRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories\EloquentStatusCategoryDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories\EloquentTaxDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Shared\Tax\Contracts\TaxDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories\EloquentTaxDtoRepository::class
        );

        $this->app->bind(
            \App\Domain\Shared\Console\Contracts\ArtisanCommandsDtoRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories\EloquentArtisanCommandsDtoRepository::class
        );
        $this->app->bind(
            \App\Application\Shared\Console\Contracts\ArtisanCommandsRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories\EloquentArtisanCommandsRepository::class
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
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
    }
}
