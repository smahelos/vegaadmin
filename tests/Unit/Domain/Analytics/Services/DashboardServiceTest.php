<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository;
use App\Domain\Analytics\Services\DashboardService as AnalyticsDashboardService;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DashboardServiceTest extends TestCase
{
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
}
