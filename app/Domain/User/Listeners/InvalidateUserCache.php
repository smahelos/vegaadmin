<?php

namespace App\Domain\User\Listeners;

use App\Domain\Analytics\Contracts\DashboardServiceInterface as AnalyticsDashboardServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\User\Events\UserDataChanged;

class InvalidateUserCache
{
    /**
     * Cache service instance
     *
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * Dashboard service instance
     *
     * @var AnalyticsDashboardServiceInterface
     */
    protected $dashboardService;

    /**
     * Create the event listener
     */
    public function __construct(
        CacheServiceInterface $cacheService,
        AnalyticsDashboardServiceInterface $dashboardService,
    ) {
        $this->cacheService = $cacheService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Handle the event
     */
    public function handle(UserDataChanged $event): void
    {
        // Invalidate user-specific dashboard cache
        $this->dashboardService->invalidateUserCache($event->userId);

        // Invalidate specific cache based on change type
        switch ($event->changeType) {
            case 'invoice':
                $this->cacheService->invalidateTags([
                    'analytics',
                    'analytics.user.' . $event->userId,
                    'analytics.monthly',
                ]);
                break;
            case 'client':
                $this->cacheService->invalidateTags([
                    'analytics',
                    'analytics.user.' . $event->userId,
                    'analytics.clients',
                ]);
                break;
            case 'supplier':
                $this->cacheService->invalidateTags(['analytics', 'analytics.user.' . $event->userId]);
                break;
            default:
                // Invalidate all user cache
                $this->cacheService->invalidateTags(['analytics.user.' . $event->userId]);
                break;
        }
    }
}
