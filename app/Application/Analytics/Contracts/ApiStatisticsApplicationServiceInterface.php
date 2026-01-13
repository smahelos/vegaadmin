<?php

namespace App\Application\Analytics\Contracts;

use Illuminate\Http\Request;

/**
 * Application layer service interface for API statistics endpoints.
 * Keeps controllers thin by encapsulating query building and aggregation logic.
 */
interface ApiStatisticsApplicationServiceInterface
{
    /**
     * Monthly revenue statistics (total + paid) grouped by YYYY-MM.
     * @return array<int, array<string, mixed>>
     */
    public function monthlyRevenue(Request $request): array;

    /**
     * Top client revenue statistics for authenticated user.
     * @return array<int, array<string, mixed>>
     */
    public function clientRevenue(Request $request): array;

    /**
     * Invoice status distribution for authenticated user.
     * @return array<int, array<string, mixed>>
     */
    public function invoiceStatus(Request $request): array;

    /**
     * Payment method usage statistics for authenticated user.
     * @return array<int, array<string, mixed>>
     */
    public function paymentMethods(Request $request): array;

    /**
     * Combined revenue vs expense per month timeline for authenticated user.
     * @return array<int, array<string, mixed>>
     */
    public function revenueExpenses(Request $request): array;
}
