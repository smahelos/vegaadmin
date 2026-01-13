<?php

namespace App\Domain\Analytics\Events\Handlers;

use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Events\DomainEventHandler;
use App\Domain\Analytics\Events\AnalyticsDataChanged;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;

/**
 * Handler for invalidating analytics cache when related data changes.
 * 
 * This ensures that analytics calculations are refreshed when underlying
 * data (invoices, clients, suppliers, etc.) is modified.
 * 
 * Handles:
 * - AnalyticsDataChanged: Invalidate relevant analytics cache based on change type
 */
class InvalidateAnalyticsCache implements DomainEventHandler
{
    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly DashboardServiceInterface $dashboardService,
        private readonly LogInterface $logger
    ) {}

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof AnalyticsDataChanged) {
            return;
        }

        try {
            // Invalidate user-specific dashboard cache
            $this->dashboardService->invalidateUserCache($event->userId);

            // Invalidate specific cache based on change type
            $this->invalidateCacheByType($event);

            $this->logger->log('info', 'Analytics cache invalidated', [
                'user_id' => $event->userId,
                'change_type' => $event->changeType,
                'entity_id' => $event->entityId,
                'event_id' => $event->getEventId(),
            ]);

        } catch (\Throwable $e) {
            $this->logger->log('error', 'Failed to invalidate analytics cache', [
                'user_id' => $event->userId,
                'change_type' => $event->changeType,
                'entity_id' => $event->entityId,
                'event_id' => $event->getEventId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate cache based on the type of change that occurred
     */
    private function invalidateCacheByType(AnalyticsDataChanged $event): void
    {
        $userTag = 'analytics.user.' . $event->userId;
        
        switch ($event->changeType) {
            case 'invoice':
                // Invoice changes affect statistics, monthly stats, and potentially client totals
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag,
                    'analytics.monthly',
                    'analytics.statistics',
                    'analytics.clients'
                ]);
                break;

            case 'client':
                // Client changes affect client listings and potentially statistics
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag,
                    'analytics.clients',
                    'analytics.statistics'
                ]);
                break;

            case 'supplier':
                // Supplier changes affect statistics
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag,
                    'analytics.statistics'
                ]);
                break;

            case 'statistics':
                // Direct statistics changes - invalidate all statistics
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag,
                    'analytics.statistics'
                ]);
                break;

            case 'monthly':
                // Monthly data changes
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag,
                    'analytics.monthly'
                ]);
                break;

            case 'dashboard':
                // Complete dashboard refresh
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag,
                    'analytics.monthly',
                    'analytics.statistics',
                    'analytics.clients'
                ]);
                break;

            default:
                // Invalidate all user analytics cache for unknown change types
                $this->cacheService->invalidateTags([
                    'analytics',
                    $userTag
                ]);
                break;
        }
    }

    public function canHandle(DomainEvent $event): bool
    {
        return $event instanceof AnalyticsDataChanged;
    }

    public function getPriority(): int
    {
        return 80; // Lower priority than usage recording, higher than notifications
    }
}
