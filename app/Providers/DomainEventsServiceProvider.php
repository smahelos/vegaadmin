<?php

namespace App\Providers;

use App\Domain\Analytics\Events\AnalyticsDataChanged;
use App\Domain\Analytics\Events\Handlers\InvalidateAnalyticsCache;
use App\Domain\Invoice\Events\Handlers\UsageRecordingHandler as InvoiceUsageRecordingHandler;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Product\Events\Handlers\UsageRecordingHandler as ProductUsageRecordingHandler;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Shared\Events\DomainEventDispatcher;
use App\Domain\Shared\Log\Contracts\LogInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Domain Events system.
 *
 * Registers all event handlers with the DomainEventDispatcher
 * to ensure proper event handling throughout the application.
 */
class DomainEventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register DomainEventDispatcher as singleton so it persists throughout the application lifecycle
        $this->app->singleton(DomainEventDispatcher::class, function ($app) {
            return new DomainEventDispatcher($app->make(LogInterface::class));
        });
    }

    public function boot(): void
    {
        // Get the domain event dispatcher from service container
        $dispatcher = app(DomainEventDispatcher::class);

        // Register all event handlers
        $this->registerEventHandlers($dispatcher);
    }

    /**
     * Register all domain event handlers with the dispatcher
     */
    private function registerEventHandlers(DomainEventDispatcher $dispatcher): void
    {
        // Usage Recording Handler - handles product creation usage tracking
        $dispatcher->register(
            [
                ProductCreated::class,
            ],
            app(ProductUsageRecordingHandler::class),
        );

        // Usage Recording Handler - handles invoice creation usage tracking
        $dispatcher->register(
            [
                InvoiceCreated::class,
            ],
            app(InvoiceUsageRecordingHandler::class),
        );

        // Analytics Cache Invalidation Handler - handles analytics data changes
        $dispatcher->register(
            [
                AnalyticsDataChanged::class,
            ],
            app(InvalidateAnalyticsCache::class),
        );
    }
}
