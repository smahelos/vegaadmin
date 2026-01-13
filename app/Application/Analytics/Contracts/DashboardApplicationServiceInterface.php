<?php

namespace App\Application\Analytics\Contracts;

use App\Models\User;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Analytics\DTO\ClientTotalDTO;

/**
 * Application Analytics Dashboard contract.
 * Provides aggregated statistics and related dashboard data for a user.
 */
interface DashboardApplicationServiceInterface
{
    /**
     * Get all dashboard data (aggregated wrapper call).
     *
     * @param User $user Authenticated user
     * @return array{statistics:UserStatisticsDTO,statistics_formatted:array,monthly_stats:array<int,MonthlyStatDTO>,monthly_stats_formatted:array,clients:array<int,ClientTotalDTO>,clients_formatted:array} Consolidated dashboard payload
     */
    public function getDashboardData(User $user): array;
}
