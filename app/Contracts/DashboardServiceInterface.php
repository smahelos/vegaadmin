<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface DashboardServiceInterface
{
    /**
     * Get user statistics for dashboard
     *
     * @param User $user
     * @return array
     */
    public function getUserStatistics(User $user): array;

    /**
     * Get monthly statistics for charts
     *
     * @param User $user
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStatistics(User $user, int $months = 6): Collection;

    /**
     * Get clients with their total invoice amounts
     *
     * @param User $user
     * @return Collection
     */
    public function getClientsWithInvoiceTotals(User $user): Collection;

    /**
     * Get all dashboard data in one call
     *
     * @param User $user
     * @return array
     */
    public function getDashboardData(User $user): array;

    /**
     * Invalidate user dashboard cache
     *
     * @param User $user
     * @return bool
     */
    public function invalidateUserCache(User $user): bool;
}
