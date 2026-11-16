<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;

final class DashboardService
{
    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly AnalyticsReadRepositoryInterface $analytics,
    ) {}

    public function getUserStatistics(ReadScope $scope): UserStatisticsDTO
    {
        return $this->cacheService->remember(
            'analytics:user:' . $scope->owner->toInt() . ':stats',
            fn (): UserStatisticsDTO => $this->analytics->getUserStats($scope),
            600,
            ['analytics.user.' . $scope->owner->toInt()],
        );
    }

    /** @return list<CustomerTotalDTO> */
    public function getCustomersWithInvoiceTotals(ReadScope $scope): array
    {
        return $this->cacheService->remember(
            'analytics:user:' . $scope->owner->toInt() . ':customers',
            fn (): array => $this->analytics->getCustomersWithTotals($scope),
            600,
            ['analytics.user.' . $scope->owner->toInt()],
        );
    }

    public function invalidateUserCache(int $userId): bool
    {
        return $this->cacheService->invalidateTags(['analytics.user.' . $userId]);
    }
}
