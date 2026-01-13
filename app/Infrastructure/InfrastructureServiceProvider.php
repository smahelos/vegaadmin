<?php

namespace App\Infrastructure;

use Illuminate\Support\ServiceProvider;

/**
 * Infrastructure layer service provider.
 *
 * Registers repository bindings to concrete infrastructure implementations
 * and any framework-dependent bootstrapping (observers, external adapters).
 */
class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSharedInfrastructureAdapters();
        $this->registerRepositoryImplementations();
        $this->registerAuthorizationServices();
        $this->registerExternalServiceAdapters();
    }

    /**
     * Register shared infrastructure adapters for domain services.
     */
    private function registerSharedInfrastructureAdapters(): void
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

        // Locale adapter for Domain
        $this->app->bind(
            \App\Domain\User\Contracts\Locale::class,
            \App\Infrastructure\Shared\Locale\Services\LaravelLocaleAdapter::class
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

        // Invoice reminders notifier
        $this->app->bind(
            \App\Domain\Invoice\Notifications\Contracts\InvoiceReminderNotifierInterface::class,
            \App\Infrastructure\Notifications\Invoice\LaravelInvoiceReminderNotifier::class
        );

        // Transaction boundary
        $this->app->bind(
            \App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Shared\Database\EloquentTransactionBoundary::class
        );
    }

    /**
     * Register all Eloquent repository implementations.
     */
    private function registerRepositoryImplementations(): void
    {
        // Analytics repositories
        $this->app->bind(
            \App\Application\Analytics\Contracts\AnalyticsReadRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentAnalyticsReadRepository::class
        );
        $this->app->bind(
            \App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentAnalyticsDtoReadRepository::class
        );
        $this->app->bind(
            \App\Application\Analytics\Contracts\ApiStatisticsRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentApiStatisticsRepository::class
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
        $this->app->bind(
            \App\Domain\Invoice\Contracts\InvoiceProductsSyncRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceProductsSyncRepository::class
        );

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

        // User repositories
        $this->app->bind(
            \App\Domain\User\Contracts\EntityLimitRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EntityLimitRepository::class
        );
        $this->app->bind(
            \App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EntityLimitUsageRepository::class
        );
        $this->app->bind(
            \App\Domain\User\Contracts\UserReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserDtoRepository::class
        );
        $this->app->bind(
            \App\Domain\User\Contracts\UserWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserDtoRepository::class
        );

        // Content repositories
        $this->app->bind(
            \App\Application\Content\Contracts\PageReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageRepository::class
        );
        $this->app->bind(
            \App\Application\Content\Contracts\PageWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageRepository::class
        );
        $this->app->bind(
            \App\Domain\Content\Contracts\PageDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageDtoRepository::class
        );
        $this->app->bind(
            \App\Domain\Content\Contracts\PageDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Content\Repositories\EloquentPageDtoRepository::class
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

        // Payment repositories
        $this->registerPaymentRepositories();
    }

    /**
     * Register payment-related repositories.
     */
    private function registerPaymentRepositories(): void
    {
        // Payment repositories
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentWriteRepository::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\PaymentReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentReadRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoWriteRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentDtoReadRepository::class
        );

        // Subscription repositories
        $this->app->bind(
            \App\Application\Payment\Contracts\SubscriptionWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionWriteRepository::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\SubscriptionReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionReadRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoWriteRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentSubscriptionDtoReadRepository::class
        );

        // Payment Method repositories
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\PaymentMethodDtoWriteRepositoryInterface::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentMethodDtoRepository::class
        );
        $this->app->bind(
            \App\Application\Payment\Contracts\BankRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentBankRepository::class
        );
        $this->app->bind(
            \App\Domain\Payment\Contracts\BankDtoRepository::class,
            \App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentBankDtoRepository::class
        );
    }

    /**
     * Register authorization services.
     */
    private function registerAuthorizationServices(): void
    {
        // Authorization adapter (Backpack + Spatie) for Application layer
        $this->app->bind(
            \App\Application\User\Contracts\UserAuthorizationAdapterInterface::class,
            \App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter::class
        );

        // CRUD access service for Backpack
        $this->app->bind(
            \App\Application\User\Contracts\CrudAccessServiceInterface::class,
            \App\Infrastructure\Authorization\Services\CrudAccessService::class
        );

        // User permissions service
        $this->app->bind(
            \App\Domain\User\Contracts\UserPermissionServiceInterface::class,
            \App\Infrastructure\Services\User\LaravelUserPermissionService::class
        );
    }

    /**
     * Register external service adapters and providers.
     */
    private function registerExternalServiceAdapters(): void
    {
        // Infrastructure product form data service
        $this->app->bind(
            \App\Infrastructure\Interfaces\Product\ProductFormDataServiceInterface::class,
            \App\Infrastructure\Services\Product\ProductFormDataService::class
        );

        // Payment providers
        $this->app->bind(
            \App\Domain\Payment\Contracts\QrPaymentServiceInterface::class,
            \App\Domain\Payment\Services\QrPaymentService::class
        );
        // $this->app->bind(
        //     \App\Domain\Payment\Contracts\QrCodeRendererInterface::class,
        //     \App\Infrastructure\Renderers\Qr\SimpleQrCodeRenderer::class
        // );
        $this->app->singleton(
            \App\Infrastructure\Providers\Payment\GoPayGateway::class
        );
    }

    public function boot(): void
    {
        // Eloquent observers registration (Domain-specific observers)
        \App\Models\Invoice::observe(\App\Infrastructure\Persistence\Eloquent\Invoice\Observers\InvoiceObserver::class);
        \App\Models\Client::observe(\App\Infrastructure\Persistence\Eloquent\Party\Observers\ClientObserver::class);
        \App\Models\Supplier::observe(\App\Infrastructure\Persistence\Eloquent\Party\Observers\SupplierObserver::class);
        \App\Models\Product::observe(\App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductObserver::class);
        \App\Models\ProductCategory::observe(\App\Infrastructure\Persistence\Eloquent\Product\Observers\ProductCategoryObserver::class);
        \App\Models\Tax::observe(\App\Infrastructure\Persistence\Eloquent\Invoice\Observers\TaxObserver::class);

        // Configure default hasher for UserPassword VO
        try {
            /** @var \App\Domain\Shared\Hash\Contracts\HashInterface $hasher */
            $hasher = $this->app->make(\App\Domain\Shared\Hash\Contracts\HashInterface::class);
            \App\Domain\User\ValueObjects\UserPassword::setHasher($hasher);
        } catch (\Throwable $e) {
            // During some console contexts this may fail; VO will throw if used without hasher.
        }
    }
}
