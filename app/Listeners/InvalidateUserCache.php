<?php

namespace App\Listeners;

use App\Events\UserDataChanged;
use App\Contracts\DashboardServiceInterface;
use App\Contracts\CacheServiceInterface;

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
     * @var DashboardServiceInterface
     */
    protected $dashboardService;

    /**
     * Create the event listener
     *
     * @param CacheServiceInterface $cacheService
     * @param DashboardServiceInterface $dashboardService
     */
    public function __construct(
        CacheServiceInterface $cacheService,
        DashboardServiceInterface $dashboardService
    ) {
        $this->cacheService = $cacheService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Handle the event
     *
     * @param UserDataChanged $event
     * @return void
     */
    public function handle(UserDataChanged $event): void
    {
        // Invalidate user-specific dashboard cache
        $this->dashboardService->invalidateUserCache($event->user);

        // Invalidate specific cache based on change type
        switch ($event->changeType) {
            case 'invoice':
                $this->cacheService->invalidateTags(['dashboard', "user_{$event->user->id}", 'monthly_stats']);
                break;
            case 'client':
                $this->cacheService->invalidateTags(['dashboard', "user_{$event->user->id}", 'clients']);
                break;
            case 'supplier':
                $this->cacheService->invalidateTags(['dashboard', "user_{$event->user->id}"]);
                break;
            default:
                // Invalidate all user cache
                $this->cacheService->invalidateTags(["user_{$event->user->id}"]);
                break;
        }
    }
}
