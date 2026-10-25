<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository;
use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\Services\DashboardService as AnalyticsDashboardService;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
    private AnalyticsDashboardService $service;

    private CacheServiceInterface $mockCacheService;

    private AnalyticsDtoReadRepository $mockAnalyticsRepo;

    protected function setUp(): void
    {
        parent::setUp();
        // Lightweight stub for cache service (only methods used inside DashboardService)
        $this->mockCacheService = new class implements CacheServiceInterface
        {
            public function remember(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): mixed {
                return is_callable($data) ? $data() : $data;
            }

            public function get(string $key): mixed
            {
                return null;
            }

            public function put(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): bool {
                return true;
            }

            public function forget(string $key): bool
            {
                return true;
            }

            public function invalidateTags(array $tags): bool
            {
                return true;
            }

            public function userKey(int $userId, string $suffix): string
            {
                return 'user_' . $userId . '_' . $suffix;
            }

            public function globalKey(string $suffix): string
            {
                return 'global_' . $suffix;
            }

            public function increment(string $key, int $amount = 1): bool
            {
                return true;
            }
        };

        // Stub analytics repository with deterministic returns
        $this->mockAnalyticsRepo = new class implements AnalyticsDtoReadRepository
        {
            public function getUserStats(int $userId): array
            {
                return [
                    'invoice_count' => 3,
                    'client_count' => 2,
                    'suppliers_count' => 1,
                    'total_amount' => 99.99,
                ];
            }

            public function getMonthlyStats(int $userId, int $months): array
            {
                return [
                    ['month' => '2025-01', 'total' => 25.50],
                ];
            }

            public function getClientsWithTotals(int $userId): array
            {
                return [
                    [
                        'client_id' => 42,
                        'client_name' => 'Client A',
                        'total' => 74.10,
                    ],
                ];
            }
        };

        // Build service instance providing required constructor deps
        $this->service = new AnalyticsDashboardService($this->mockCacheService, $this->mockAnalyticsRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // Reflection-based builder removed; we directly construct the service with explicit stubs

    #[Test]
    public function invalidate_user_cache_uses_tag_invalidation(): void
    {
        $stub = new class implements CacheServiceInterface
        {
            public array $invalidated = [];

            public function remember(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): mixed {
                return is_callable($data) ? $data() : $data;
            }

            public function get(string $key): mixed
            {
                return null;
            }

            public function put(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): bool {
                return true;
            }

            public function forget(string $key): bool
            {
                return true;
            }

            public function invalidateTags(array $tags): bool
            {
                $this->invalidated[] = $tags;

                return true;
            }

            public function userKey(int $userId, string $suffix): string
            {
                return 'user_' . $userId . '_' . $suffix;
            }

            public function globalKey(string $suffix): string
            {
                return 'global_' . $suffix;
            }

            public function increment(string $key, int $amount = 1): bool
            {
                return true;
            }
        };
        $analytics = new class implements AnalyticsDtoReadRepository
        {
            public function getUserStats(int $userId): array
            {
                return [
                    'invoice_count' => 0,
                    'client_count' => 0,
                    'suppliers_count' => 0,
                    'total_amount' => 0.0,
                ];
            }

            public function getMonthlyStats(int $userId, int $months): array
            {
                return [];
            }

            public function getClientsWithTotals(int $userId): array
            {
                return [];
            }
        };
        $service = new AnalyticsDashboardService($stub, $analytics);
        $userId = 777;
        $this->assertTrue($service->invalidateUserCache($userId));
        $flat = collect($stub->invalidated)->flatten();
        $this->assertTrue($flat->contains(fn ($t) => str_starts_with($t, 'analytics.user.')));
    }

    #[Test]
    public function get_dashboard_data_returns_expected_structures(): void
    {
        $cache = $this->mockCacheService; // use default pass-through cache
        $analytics = $this->mockAnalyticsRepo; // use deterministic analytics stub
        $service = new AnalyticsDashboardService($cache, $analytics);
        $userId = UserId::fromInt(900);
        $data = $service->getDashboardData($userId);
        // Domain returns DTOs/collections; formatted structures belong to Application layer
        $this->assertArrayHasKey('statistics', $data);
        $this->assertArrayHasKey('monthly_stats', $data);
        $this->assertArrayHasKey('clients', $data);

        $this->assertInstanceOf(UserStatisticsDTO::class, $data['statistics']);
        $this->assertIsArray($data['monthly_stats']);
        $this->assertIsArray($data['clients']);

        // Check inner item types (if arrays are not empty)
        if (! empty($data['monthly_stats'])) {
            $this->assertInstanceOf(MonthlyStatDTO::class, $data['monthly_stats'][0]);
        }
        if (! empty($data['clients'])) {
            $this->assertInstanceOf(ClientTotalDTO::class, $data['clients'][0]);
        }
    }
}
