<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Contracts;

use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\PlatformStatisticsDTO;
use App\Domain\Analytics\DTO\UserActivityStatisticsDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use DateTimeImmutable;

interface AnalyticsReadRepositoryInterface
{
    public function getUserStats(ReadScope $scope): UserStatisticsDTO;

    /** @return list<CustomerTotalDTO> */
    public function getCustomersWithTotals(ReadScope $scope): array;

    /**
     * Counts and revenue of every owner; recent counts from `$recentSince`, monthly revenue from `$monthStart`.
     */
    public function getPlatformStatistics(
        AllOwnersScope $scope,
        DateTimeImmutable $recentSince,
        DateTimeImmutable $monthStart,
    ): PlatformStatisticsDTO;

    public function getUserActivity(AllOwnersScope $scope, int $mostActiveLimit): UserActivityStatisticsDTO;
}
