<?php

namespace App\Services;

use App\Contracts\DashboardServiceInterface;
use App\Contracts\CacheServiceInterface;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService implements DashboardServiceInterface
{
    /**
     * Cache service instance
     *
     * @var CacheServiceInterface
     */
    protected $cacheService;

    /**
     * Cache TTL for dashboard statistics (10 minutes)
     */
    private const STATS_CACHE_TTL = 600;

    /**
     * Cache TTL for monthly statistics (30 minutes)
     */
    private const MONTHLY_CACHE_TTL = 1800;

    /**
     * Constructor
     *
     * @param CacheServiceInterface $cacheService
     */
    public function __construct(CacheServiceInterface $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    /**
     * Get user statistics for dashboard
     *
     * @param User $user
     * @return array
     */
    public function getUserStatistics(User $user): array
    {
        $cacheKey = $this->cacheService->userKey($user->id, 'dashboard_stats');
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($user) {
                return [
                    'invoice_count' => Invoice::where('user_id', $user->id)->count(),
                    'client_count' => Client::where('user_id', $user->id)->count(),
                    'suppliers_count' => Supplier::where('user_id', $user->id)->count(),
                    'total_amount' => Invoice::where('user_id', $user->id)->sum('payment_amount'),
                ];
            },
            self::STATS_CACHE_TTL,
            ['dashboard', "user_{$user->id}"]
        );
    }

    /**
     * Get monthly statistics for charts
     *
     * @param User $user
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStatistics(User $user, int $months = 6): Collection
    {
        $cacheKey = $this->cacheService->userKey($user->id, "monthly_stats_{$months}");
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($user, $months) {
                return DB::table('invoices')
                    ->join('clients', 'invoices.client_id', '=', 'clients.id')
                    ->where('clients.user_id', $user->id)
                    ->whereNotNull('invoices.issue_date')
                    ->where('invoices.issue_date', '>=', now()->subMonths($months))
                    ->select(
                        DB::raw('DATE_FORMAT(invoices.issue_date, "%Y-%m") as month'),
                        DB::raw('SUM(invoices.payment_amount) as total')
                    )
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
            },
            self::MONTHLY_CACHE_TTL,
            ['dashboard', "user_{$user->id}", 'monthly_stats']
        );
    }

    /**
     * Get clients with their total invoice amounts
     *
     * @param User $user
     * @return Collection
     */
    public function getClientsWithInvoiceTotals(User $user): Collection
    {
        $cacheKey = $this->cacheService->userKey($user->id, 'clients_with_totals');
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($user) {
                return Client::where('user_id', $user->id)
                    ->with(['invoices' => function($query) {
                        $query->select('client_id', DB::raw('SUM(payment_amount) as total'))
                              ->groupBy('client_id');
                    }])
                    ->get();
            },
            self::STATS_CACHE_TTL,
            ['dashboard', "user_{$user->id}", 'clients']
        );
    }

    /**
     * Invalidate user dashboard cache
     *
     * @param User $user
     * @return bool
     */
    public function invalidateUserCache(User $user): bool
    {
        return $this->cacheService->invalidateTags(["user_{$user->id}"]);
    }

    /**
     * Get all dashboard data in one call
     *
     * @param User $user
     * @return array
     */
    public function getDashboardData(User $user): array
    {
        return [
            'statistics' => $this->getUserStatistics($user),
            'monthly_stats' => $this->getMonthlyStatistics($user),
            'clients' => $this->getClientsWithInvoiceTotals($user),
        ];
    }
}
