<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Contracts;

use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\User\ValueObjects\UserId;

interface AnalyticsReadRepositoryInterface
{
    public function getUserStats(UserId $userId): UserStatisticsDTO;

    /** @return list<CustomerTotalDTO> */
    public function getCustomersWithTotals(UserId $userId): array;
}
