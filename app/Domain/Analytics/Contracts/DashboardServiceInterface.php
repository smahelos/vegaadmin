<?php

namespace App\Domain\Analytics\Contracts;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Analytics\DTO\ClientTotalDTO;

/**
 * Domain Analytics Dashboard contract.
 * Provides aggregated statistics and related dashboard data for a user.
 */
interface DashboardServiceInterface
{
    /**
     * Get user statistics for dashboard (counts + totals).
     *
     * @param UserId $userId Authenticated user ID whose statistics are requested
     * @return UserStatisticsDTO Structured statistics object
     */
    public function getUserStatistics(UserId $userId): UserStatisticsDTO;

    /**
     * Get monthly aggregated statistics for charts (last N months).
     *
     * @param UserId $userId Authenticated user ID whose monthly stats are requested
     * @param int $months Number of past months to include (default 6)
     * @return array<int, MonthlyStatDTO> Collection of monthly stat DTOs
     */
    public function getMonthlyStatistics(UserId $userId, int $months = 6): array;

    /**
     * Get clients with their total invoice amounts for the user.
     *
     * @param UserId $userId Authenticated user ID whose clients are aggregated
     * @return array<int, ClientTotalDTO> DTO array for client totals
     */
    public function getClientsWithInvoiceTotals(UserId $userId): array;

    /**
     * Get all dashboard data (aggregated wrapper call).
     *
     * @param UserId $userId Authenticated user ID
     * @return array{statistics:UserStatisticsDTO,monthly_stats:array<int, MonthlyStatDTO>,clients:array<int, ClientTotalDTO>} Consolidated dashboard payload
     */
    public function getDashboardData(UserId $userId): array;

    /**
     * Invalidate all cached dashboard data for a user.
     *
     * @param int $userId Authenticated user
     * @return bool True on successful invalidation
     */
    public function invalidateUserCache(int $userId): bool;
}
