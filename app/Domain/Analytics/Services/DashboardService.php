<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\User\ValueObjects\UserId;

final class DashboardService
{
    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly AnalyticsReadRepositoryInterface $analytics,
    ) {}

    public function getUserStatistics(UserId $userId): UserStatisticsDTO
    {
        return $this->cacheService->remember(
            'analytics:user:' . $userId->toInt() . ':stats',
            fn (): UserStatisticsDTO => $this->analytics->getUserStats($userId),
            600,
            ['analytics.user.' . $userId->toInt()],
        );
    }

    /** @return list<CustomerTotalDTO> */
    public function getCustomersWithInvoiceTotals(UserId $userId): array
    {
        return $this->cacheService->remember(
            'analytics:user:' . $userId->toInt() . ':customers',
            fn (): array => $this->analytics->getCustomersWithTotals($userId),
            600,
            ['analytics.user.' . $userId->toInt()],
        );
    }

    public function invalidateUserCache(int $userId): bool
    {
        return $this->cacheService->invalidateTags(['analytics.user.' . $userId]);
    }
}
