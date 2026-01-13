<?php

namespace App\Providers;

use App\Domain\Shared\Events\DomainEventDispatcher;
use App\Domain\Party\Events\Handlers\UsageRecordingHandler as PartyUsageRecordingHandler;
use App\Domain\Product\Events\Handlers\UsageRecordingHandler as ProductUsageRecordingHandler;
use App\Domain\Invoice\Events\Handlers\UsageRecordingHandler as InvoiceUsageRecordingHandler;
use App\Domain\Analytics\Events\Handlers\InvalidateAnalyticsCache;
use Illuminate\Support\ServiceProvider;
use App\Domain\Shared\Log\Contracts\LogInterface;

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
        // Usage Recording Handler - handles client/supplier creation usage tracking
        $dispatcher->register(
            [
                \App\Domain\Party\Events\ClientCreated::class,
                \App\Domain\Party\Events\SupplierCreated::class,
            ],
            app(PartyUsageRecordingHandler::class)
        );

        // Usage Recording Handler - handles product creation usage tracking
        $dispatcher->register(
            [
                \App\Domain\Product\Events\ProductCreated::class,
            ],
            app(ProductUsageRecordingHandler::class)
        );

        // Usage Recording Handler - handles invoice creation usage tracking
        $dispatcher->register(
            [
                \App\Domain\Invoice\Events\InvoiceCreated::class,
            ],
            app(InvoiceUsageRecordingHandler::class)
        );

        // Analytics Cache Invalidation Handler - handles analytics data changes
        $dispatcher->register(
            [
                \App\Domain\Analytics\Events\AnalyticsDataChanged::class,
            ],
            app(InvalidateAnalyticsCache::class)
        );

        // Future event handlers can be registered here as the system grows
        // Example:
        // $dispatcher->register(
        //     [\App\Domain\Party\Events\ClientUpdated::class],
        //     app(EmailNotificationHandler::class)
        // );
    }
}
