<?php

namespace App\Application\Analytics\Contracts;

/**
 * Domain contract for API statistics operations. Infrastructure provides Eloquent/DB implementations.
 */
interface ApiStatisticsRepository
{
    /**
     * Get monthly revenue query
     */
    public function getMonthlyRevenueQuery(): mixed;

    /**
     * Gets client revenue query.
     */
    public function getClientRevenueQuery(string $startDate): mixed;

    /**
     * Gets revenue statistics query.
     */
    public function getRevenueQuery(): mixed;

    /**
     * Gets expense statistics query.
     */
    public function getExpenseQuery(): mixed;

    /**
     * Gets invoice status distribution query.
     */
    public function getInvoiceStatusQuery(): mixed;

    /**
     * Gets payment method usage statistics query.
     */
    public function getPaymentMethodsQuery(): mixed;
}
