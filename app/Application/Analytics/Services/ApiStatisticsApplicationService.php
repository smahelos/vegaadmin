<?php

namespace App\Application\Analytics\Services;

use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Application service implementing API statistics aggregation logic.
 * NOTE: Uses models directly; controllers remain free of query construction.
 */
class ApiStatisticsApplicationService implements ApiStatisticsApplicationServiceInterface
{
    public function __construct(
        protected \App\Application\Analytics\Contracts\ApiStatisticsRepository $apiStatisticsRepository
    ) {}

    public function monthlyRevenue(Request $request): array
    {
        $query = $this->apiStatisticsRepository->getMonthlyRevenueQuery();

        $query = $this->applyFilters($query, $request);
        return $query->get()->toArray();
    }

    public function clientRevenue(Request $request): array
    {
        $timeRange = $request->input('time_range', 'year');
        $startDate = $this->calculateStartDate($timeRange);

        $query = $this->apiStatisticsRepository->getClientRevenueQuery($startDate);

        return $query->get()->toArray();
    }

    public function invoiceStatus(Request $request): array
    {
        $query = $this->apiStatisticsRepository->getInvoiceStatusQuery();

        $query = $this->applyFilters($query, $request);
        return $query->get()->toArray();
    }

    public function paymentMethods(Request $request): array
    {
        $query = $this->apiStatisticsRepository->getPaymentMethodsQuery();

        $query = $this->applyFilters($query, $request);
        return $query->get()->toArray();
    }

    public function revenueExpenses(Request $request): array
    {
        $revenueQuery = $this->apiStatisticsRepository->getRevenueQuery();
        $revenueQuery = $this->applyFilters($revenueQuery, $request);

        try {
            $expenseQuery = $this->apiStatisticsRepository->getExpenseQuery();
            $expenseQuery = $this->applyTimeRangeFilter($expenseQuery, $request);
            $expenseData = $expenseQuery->get();
        } catch (\Exception $e) {
            $expenseData = collect([]);
        }

        $revenueData = $revenueQuery->get();
        return $revenueData->concat($expenseData)->sortBy('month')->values()->toArray();
    }

    // Internal helpers (copied from original controller, kept private)
    private function calculateStartDate(string $timeRange): Carbon
    {
        $now = now();
        return match ($timeRange) {
            'month' => $now->subMonth(),
            'quarter' => $now->subMonths(3),
            'half_year', '6_months' => $now->subMonths(6),
            'year' => $now->subYear(),
            default => $now->subYear(),
        };
    }

    private function applyFilters($query, Request $request)
    {
        $query = $this->applyTimeRangeFilter($query, $request);
        if ($request->has('clients')) {
            $clients = $request->input('clients');
            if (!empty($clients)) {
                $query->whereIn('client_id', $clients);
            }
        }
        return $query;
    }

    private function applyTimeRangeFilter($query, Request $request)
    {
        $modelTable = $query->getModel()->getTable();
        $dateColumn = '';
        if ($modelTable === 'invoices') {
            $dateColumn = 'issue_date';
        } elseif ($modelTable === 'expenses') {
            $dateColumn = 'expense_date';
        } else {
            return $query; // Unknown table, skip
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            if ($modelTable === 'invoices') {
                return $query->where(function ($q) use ($dateColumn, $dateFrom, $dateTo) {
                    $q->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') >= ?", [$dateFrom])
                        ->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') <= ?", [$dateTo]);
                });
            }
            return $query->whereBetween($dateColumn, [$dateFrom, $dateTo]);
        }

        $timeRange = $request->input('time_range', 'year');
        $now = Carbon::now();
        switch ($timeRange) {
            case '6month':
                $startDate = $now->copy()->subMonths(6)->startOfMonth();
                break;
            case 'quarter':
                $startDate = $now->copy()->subMonths(3)->startOfMonth();
                break;
            case 'month':
                $startDate = $now->copy()->subMonth()->startOfMonth();
                break;
            case 'year':
            default:
                $startDate = $now->copy()->subYear()->startOfMonth();
                break;
        }

        if ($modelTable === 'invoices') {
            return $query->whereRaw("STR_TO_DATE($dateColumn, '%Y-%m-%d') >= ?", [$startDate->format('Y-m-d')]);
        }
        return $query->where($dateColumn, '>=', $startDate->format('Y-m-d'));
    }
}
