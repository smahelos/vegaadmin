<?php

namespace App\Application\Analytics\Contracts;


/**
 * Domain contract for API statistics operations. Infrastructure provides Eloquent/DB implementations.
 */
interface ApiStatisticsRepository
{
    /**
     * Get monthly revenue query
     * @return mixed
     */
    public function getMonthlyRevenueQuery(): mixed;

    /**
     * Gets client revenue query.
     * @param string $startDate
     * @return mixed
     */
    public function getClientRevenueQuery(string $startDate): mixed;

    /**
     * Gets revenue statistics query.
     * @return mixed
     */
    public function getRevenueQuery(): mixed;

    /**
     * Gets expense statistics query.
     * @return mixed
     */
    public function getExpenseQuery(): mixed;

    /**
     * Gets invoice status distribution query.
     * @return mixed
     */
    public function getInvoiceStatusQuery(): mixed;

    /**
     * Gets payment method usage statistics query.
     * @return mixed
     */
    public function getPaymentMethodsQuery(): mixed;
}
