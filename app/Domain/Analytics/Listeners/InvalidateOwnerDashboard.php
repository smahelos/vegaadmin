<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Listeners;

use App\Domain\Analytics\Services\DashboardService;
use App\Domain\Party\Events\PartyEvent;

/**
 * The dashboard counts and lists the owner's businesses and contacts, so their changes drop the owner's cached
 * dashboard once the change is committed.
 */
final class InvalidateOwnerDashboard
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function handle(PartyEvent $event): void
    {
        $userId = $event->userId->toInt();

        $this->dashboard->invalidateUserCache($userId);
    }
}
