<?php

namespace App\Providers;

use App\Application\Shared\Console\Contracts\ArtisanCommandsRepositoryInterface;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use App\Application\Shared\Geography\Contracts\LocaleApplicationServiceInterface;
use App\Application\Shared\Geography\Services\CountryApplicationService;
use App\Application\Shared\Geography\Services\LocaleApplicationService;
use App\Application\Shared\Money\Contracts\CurrencyApplicationServiceInterface;
use App\Application\Shared\Money\Services\CurrencyApplicationService;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Config\Contracts\Config;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsDtoRepositoryInterface;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use App\Domain\Shared\Console\Services\ArtisanCommandsService;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\File\Config\FileUploadConfig;
use App\Domain\Shared\File\Contracts\FileUploadServiceInterface;
use App\Domain\Shared\File\Contracts\ImageProcessorInterface;
use App\Domain\Shared\Geography\Contracts\CountryServiceInterface;
use App\Domain\Shared\Geography\Services\CountryService;
use App\Domain\Shared\Hash\Contracts\HashInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\Services\CurrencyExchangeService;
use App\Domain\Shared\Money\Services\CurrencyService;
use App\Domain\Shared\Money\Services\DeterministicCurrencyExchangeService;
use App\Domain\Shared\Money\Services\MoneyFormatter;
use App\Domain\Shared\Session\Contracts\SessionInterface;
use App\Domain\Shared\Status\Contracts\StatusCategoryDtoRepositoryInterface;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Domain\Shared\Status\Contracts\StatusServiceInterface;
use App\Domain\Shared\Status\Services\StatusService;
use App\Domain\Shared\Str\Contracts\Str;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoWriteRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxServiceInterface;
use App\Domain\Shared\Tax\Services\TaxService;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\Shared\Time\Contracts\PeriodServiceInterface;
use App\Domain\Shared\Translation\Contracts\TranslatorInterface;
use App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories\EloquentArtisanCommandsDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Shared\Console\Repositories\EloquentArtisanCommandsRepository;
use App\Infrastructure\Persistence\Eloquent\Shared\Database\EloquentTransactionBoundary;
use App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories\EloquentStatusCategoryDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories\EloquentStatusDtoRepository;
use App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories\EloquentTaxDtoRepository;
use App\Infrastructure\Shared\Cache\CacheService;
use App\Infrastructure\Shared\Config\Services\LaravelConfigAdapter;
use App\Infrastructure\Shared\Events\LaravelEventPublisher;
use App\Infrastructure\Shared\File\Processors\GdImageProcessor;
use App\Infrastructure\Shared\File\Services\LaravelFileUploadService;
use App\Infrastructure\Shared\Hash\LaravelHash;
use App\Infrastructure\Shared\Http\Services\HttpClientService;
use App\Infrastructure\Shared\Log\LaravelLogger;
use App\Infrastructure\Shared\Session\LaravelSessionStore;
use App\Infrastructure\Shared\Str\Services\LaravelStrAdapter;
use App\Infrastructure\Shared\Time\CarbonClock;
use App\Infrastructure\Shared\Time\CarbonPeriodService;
use App\Infrastructure\Shared\Translation\LaravelTranslator;
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
            StatusServiceInterface::class,
            StatusService::class,
        );

        // File Upload Config wrapper (singleton)
        $this->app->singleton(FileUploadConfig::class, function () {
            return new FileUploadConfig;
        });

        // Money & Currency services
        if (config('exchange.deterministic_enabled')) {
            $this->app->bind(
                CurrencyExchangeServiceInterface::class,
                function () {
                    $raw = config('exchange.deterministic_rates');
                    $rates = null;
                    if (is_string($raw) && $raw !== '') {
                        $parsed = [];
                        foreach (explode(',', $raw) as $pair) {
                            [$c, $v] = array_pad(
                                explode(
                                    ':',
                                    $pair,
                                    2,
                                ),
                                2,
                                null,
                            );
                            if ($c && $v && is_numeric($v)) {
                                $parsed[$c] = (float) $v;
                            }
                        }
                        if ($parsed) {
                            $rates = $parsed;
                        }
                    }

                    return new DeterministicCurrencyExchangeService($rates);
                },
            );
        } else {
            $this->app->bind(
                CurrencyExchangeServiceInterface::class,
                CurrencyExchangeService::class,
            );
        }

        $this->app->bind(
            CurrencyServiceInterface::class,
            CurrencyService::class,
        );

        $this->app->bind(
            MoneyFormatterInterface::class,
            MoneyFormatter::class,
        );

        // Geography services
        $this->app->bind(
            CountryServiceInterface::class,
            CountryService::class,
        );

        // Console / Artisan Commands meta service
        $this->app->bind(
            ArtisanCommandsServiceInterface::class,
            ArtisanCommandsService::class,
        );

        // Tax service
        $this->app->bind(
            TaxServiceInterface::class,
            TaxService::class,
        );
    }

    /**
     * Infrastructure implementations: Eloquent repositories, framework adapters, external gateways.
     */
    private function registerInfrastructure(): void
    {
        // Domain events publisher
        $this->app->bind(
            EventPublisherInterface::class,
            LaravelEventPublisher::class,
        );

        // File services
        $this->app->bind(
            FileUploadServiceInterface::class,
            LaravelFileUploadService::class,
        );

        $this->app->bind(
            ImageProcessorInterface::class,
            GdImageProcessor::class,
        );

        // Translation adapter for Domain
        $this->app->bind(
            TranslatorInterface::class,
            LaravelTranslator::class,
        );

        // Cache adapter for Domain
        $this->app->bind(
            CacheServiceInterface::class,
            CacheService::class,
        );

        // Time services adapters
        $this->app->bind(
            ClockInterface::class,
            CarbonClock::class,
        );

        $this->app->bind(
            PeriodServiceInterface::class,
            CarbonPeriodService::class,
        );

        // Logging adapter for Domain
        $this->app->bind(
            LogInterface::class,
            LaravelLogger::class,
        );

        // Config adapter for Domain
        $this->app->bind(
            Config::class,
            LaravelConfigAdapter::class,
        );

        // Session adapter for Domain
        $this->app->bind(
            SessionInterface::class,
            LaravelSessionStore::class,
        );

        // Str adapter for Domain
        $this->app->bind(
            Str::class,
            LaravelStrAdapter::class,
        );

        // Hash adapter for Domain
        $this->app->bind(
            HashInterface::class,
            LaravelHash::class,
        );

        // HTTP Client
        $this->app->bind(
            HttpClientInterface::class,
            HttpClientService::class,
        );

        // Transaction boundary
        $this->app->bind(
            TransactionBoundaryInterface::class,
            EloquentTransactionBoundary::class,
        );

        // Shared repositories
        $this->app->bind(
            StatusDtoRepositoryInterface::class,
            EloquentStatusDtoRepository::class,
        );

        $this->app->bind(
            StatusCategoryDtoRepositoryInterface::class,
            EloquentStatusCategoryDtoRepository::class,
        );

        $this->app->bind(
            TaxDtoReadRepositoryInterface::class,
            EloquentTaxDtoRepository::class,
        );

        $this->app->bind(
            TaxDtoWriteRepositoryInterface::class,
            EloquentTaxDtoRepository::class,
        );

        $this->app->bind(
            ArtisanCommandsDtoRepositoryInterface::class,
            EloquentArtisanCommandsDtoRepository::class,
        );
        $this->app->bind(
            ArtisanCommandsRepositoryInterface::class,
            EloquentArtisanCommandsRepository::class,
        );
    }

    /**
     * Application use-case services, presenters and mappers.
     */
    private function registerApplicationServices(): void
    {
        // Money (Currency) application service binding
        $this->app->bind(
            CurrencyApplicationServiceInterface::class,
            CurrencyApplicationService::class,
        );

        // Geography (Country) application service binding
        $this->app->bind(
            CountryApplicationServiceInterface::class,
            CountryApplicationService::class,
        );

        // Locale application service binding
        $this->app->bind(
            LocaleApplicationServiceInterface::class,
            LocaleApplicationService::class,
        );
    }
}
