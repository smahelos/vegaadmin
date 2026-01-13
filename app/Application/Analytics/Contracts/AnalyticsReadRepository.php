<?php

namespace App\Application\Analytics\Contracts;

/**
 * Domain contract for analytics read operations. Infrastructure provides Eloquent/DB implementations.
 */
interface AnalyticsReadRepository
{
    /**
     * @return array{invoice_count:int,client_count:int,suppliers_count:int,total_amount:float}
     */
    public function getUserStats(int $userId): array;

    /**
     * @return array<int, array{month:string,total:float}>
     */
    public function getMonthlyStats(int $userId, int $months): array;

    /**
     * @return array<int, array{client_id:int,client_name:string,total:float}>
     */
    public function getClientsWithTotals(int $userId): array;
}
