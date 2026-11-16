<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Contracts;

use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

interface AnalyticsReadRepositoryInterface
{
    public function getUserStats(ReadScope $scope): UserStatisticsDTO;

    /** @return list<CustomerTotalDTO> */
    public function getCustomersWithTotals(ReadScope $scope): array;
}
