<?php

declare(strict_types=1);

namespace App\Application\Analytics\Contracts;

use App\Domain\Analytics\DTO\StatisticsFilter;
use App\Domain\User\ValueObjects\UserId;

/**
 * Dashboard chart statistics of one user.
 */
interface ApiStatisticsApplicationServiceInterface
{
    /**
     * Revenue (total and paid) per month, YYYY-MM.
     *
     * @return list<array<string, mixed>>
     */
    public function monthlyRevenue(UserId $userId, StatisticsFilter $filter): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function customerRevenue(UserId $userId, StatisticsFilter $filter): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function invoiceStatus(UserId $userId, StatisticsFilter $filter): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function paymentMethods(UserId $userId, StatisticsFilter $filter): array;

    /**
     * Revenue and expense rows per month, ordered by month.
     *
     * @return list<array<string, mixed>>
     */
    public function revenueExpenses(UserId $userId, StatisticsFilter $filter): array;
}
