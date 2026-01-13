<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository;

/**
 * DashboardService (Analytics Domain)
 * Responsible for aggregated analytical metrics exposed to UI.
 */
class DashboardService implements DashboardServiceInterface
{
    /** Cache TTL for user statistics (seconds) */
    private const STATS_CACHE_TTL = 600; // 10 min

    /** Cache TTL for monthly statistics (seconds) */
    private const MONTHLY_CACHE_TTL = 1800; // 30 min

    /** Standardized cache key namespace prefix */
    private const CACHE_PREFIX = 'analytics:user:'; // e.g. analytics:user:123:stats

    /** Default currency (system-wide base) */
    private const DEFAULT_CURRENCY = 'CZK';

    /**
     * @param CacheServiceInterface $cacheService Cache abstraction for storing analytics aggregates
     */
    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly AnalyticsDtoReadRepository $analytics,
    ) {}

    /** @inheritDoc */
    public function getUserStatistics(UserId $userId): UserStatisticsDTO
    {
        $cacheKey = self::CACHE_PREFIX . $userId->toInt() . ':stats';
        $data = $this->cacheService->remember(
            $cacheKey,
            fn () => $this->aggregateUserStats($userId),
            self::STATS_CACHE_TTL,
            ['analytics', 'analytics.user.' . $userId->toInt()]
        );
        return UserStatisticsDTO::fromArray($data, self::DEFAULT_CURRENCY);
    }

    /** @inheritDoc */
    public function getMonthlyStatistics(UserId $userId, int $months = 6): array
    {
        $cacheKey = self::CACHE_PREFIX . $userId->toInt() . ':monthly:' . $months;
        $rawData = $this->cacheService->remember(
            $cacheKey,
            fn () => $this->analytics->getMonthlyStats($userId->toInt(), $months),
            self::MONTHLY_CACHE_TTL,
            ['analytics', 'analytics.user.' . $userId->toInt(), 'analytics.monthly']
        );
        return array_map(
            fn ($row) => new MonthlyStatDTO(
                $row['month'], 
                Money::fromString(number_format((float)$row['total'], 2, '.', ''), self::DEFAULT_CURRENCY)
            ),
            $rawData
        );
    }

    /** @inheritDoc */
    public function getClientsWithInvoiceTotals(UserId $userId): array
    {
        $cacheKey = self::CACHE_PREFIX . $userId->toInt() . ':clients';
        $rawData = $this->cacheService->remember(
            $cacheKey,
            fn () => $this->analytics->getClientsWithTotals($userId->toInt()),
            self::STATS_CACHE_TTL,
            ['analytics', 'analytics.user.' . $userId->toInt(), 'analytics.clients']
        );
        return array_map(
            fn (array $c) => ClientTotalDTO::fromArray($c, self::DEFAULT_CURRENCY),
            $rawData
        );
    }

    /** @inheritDoc */
    public function invalidateUserCache(int $userId): bool
    {
        return $this->cacheService->invalidateTags(['analytics.user.' . $userId]);
    }

    /** @inheritDoc */
    public function getDashboardData(UserId $userId): array
    {
        $stats = $this->getUserStatistics($userId);
        $monthly = $this->getMonthlyStatistics($userId);
        $clients = $this->getClientsWithInvoiceTotals($userId);

        // Domain returns pure DTO/collections; formatting belongs to Application layer.
        return [
            'statistics' => $stats,
            'monthly_stats' => $monthly,
            'clients' => $clients,
        ];
    }

    /**
     * Aggregate base statistics (no caching inside; invoked by cache wrapper).
     *
     * @param UserId $userId Target user ID
     * @return array{invoice_count:int,client_count:int,suppliers_count:int,total_amount:float}
     */
    private function aggregateUserStats(UserId $userId): array
    {
        return $this->analytics->getUserStats($userId->toInt());
    }
}
