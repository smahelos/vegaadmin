<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Domain\Analytics\DTO\PlatformStatisticsDTO;
use App\Domain\Analytics\DTO\UserActivityStatisticsDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\Services\DashboardService as AnalyticsDashboardService;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;
use LogicException;
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
        $analytics = new class implements AnalyticsReadRepositoryInterface
        {
            public function getUserStats(ReadScope $scope): UserStatisticsDTO
            {
                return new UserStatisticsDTO(
                    0,
                    0,
                    0,
                    Money::fromString('0', 'CZK'),
                );
            }

            public function getCustomersWithTotals(ReadScope $scope): array
            {
                return [];
            }

            public function getPlatformStatistics(
                AllOwnersScope $scope,
                DateTimeImmutable $recentSince,
                DateTimeImmutable $monthStart,
            ): PlatformStatisticsDTO {
                throw new LogicException('Not used by the dashboard service');
            }

            public function getUserActivity(AllOwnersScope $scope, int $mostActiveLimit): UserActivityStatisticsDTO
            {
                throw new LogicException('Not used by the dashboard service');
            }
        };
        $service = new AnalyticsDashboardService($stub, $analytics);
        $userId = 777;
        $this->assertTrue(
            $service->invalidateUserCache($userId),
        );
        $this->assertSame([['analytics.user.777']], $stub->invalidated);
    }
}
