<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Listeners;

use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Party\Events\PartyEvent;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;

/**
 * The dashboard counts and lists the owner's businesses and contacts, so their changes drop the owner's cached
 * dashboard once the change is committed.
 */
final class InvalidateOwnerDashboard
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly CacheServiceInterface $cache,
    ) {}

    public function handle(PartyEvent $event): void
    {
        $userId = $event->userId->toInt();

        $this->dashboard->invalidateUserCache($userId);
        $this->cache->invalidateTags([
            'analytics',
            'analytics.user.' . $userId,
            'analytics.clients',
        ]);
    }
}
