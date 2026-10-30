<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\Analytics\Services\DashboardService as AnalyticsDashboardService;
use App\Domain\User\Events\UserDataChanged;

final class InvalidateUserCache
{
    /**
     * Create the event listener
     */
    public function __construct(
        private readonly AnalyticsDashboardService $dashboardService,
    ) {}

    /**
     * Handle the event
     */
    public function handle(UserDataChanged $event): void
    {
        $this->dashboardService->invalidateUserCache($event->userId);
    }
}
