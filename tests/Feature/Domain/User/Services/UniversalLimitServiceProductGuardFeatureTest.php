<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\Shared\Time\Contracts\PeriodServiceInterface;
use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface;
use App\Domain\User\Contracts\PermissionLimitResolverInterface;
use App\Domain\User\Services\UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UniversalLimitServiceProductGuardFeatureTest extends TestCase
{
    private UniversalLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $resolver = new class implements PermissionLimitResolverInterface
        {
            public function getUserLimit(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                string $periodType = 'daily',
            ): int {
                return 5;
            }

            public function getAllUserLimits(?int $userId): array
            {
                return [];
            }

            public function getEntityLimits(?int $userId, string $entityType): array
            {
                return [];
            }

            public function clearUserCache(int $userId): void {}

            public function clearAllCache(): void {}

            public function canUserCreateEntity(?int $userId, string $entityType): bool
            {
                return true;
            }

            public function getUserPermissionLimits(?int $userId, string $entityType): array
            {
                return [];
            }
        };
        // Minimal stub repos
        $entityLimitRepo = new class implements EntityLimitRepositoryInterface
        {
            public function getActiveByPermissions(array $permissionNames): array
            {
                return [];
            }

            public function getPermissionNamesForEntity(string $entityType, bool $includeInactive = true): array
            {
                // Simulate configured permission names for product entity
                return ['can_create_edit_product'];
            }

            public function getActiveByPermissionsFiltered(
                array $permissionNames,
                string $entityType,
                string $metricType,
                string $periodType,
            ): array {
                return [];
            }

            public function getAllEntityTypes(): array
            {
                return [
                    'invoice',
                    'client',
                    'supplier',
                    'product',
                    'expense',
                ];
            }

            public function getAllMetricTypes(): array
            {
                return ['count'];
            }

            public function getAllPeriodTypes(): array
            {
                return [
                    'daily',
                    'monthly',
                    'yearly',
                    'lifetime',
                ];
            }
        };
        $entityLimitUsageRepo = new class implements EntityLimitUsageRepositoryInterface
        {
            public function existsCurrentPeriod(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                \DateTimeImmutable $now,
            ): bool {
                return false;
            }

            public function insertUsage(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                \DateTimeImmutable $periodStart,
                \DateTimeImmutable $periodEnd,
                float|int $currentValue = 0,
            ): void {}

            public function getCurrentValue(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                \DateTimeImmutable $periodStart,
                \DateTimeImmutable $periodEnd,
            ): int|float {
                return 0;
            }

            public function incrementOrCreateCurrentPeriod(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                \DateTimeImmutable $periodStart,
                \DateTimeImmutable $periodEnd,
                int|float $value,
            ): bool {
                return true;
            }

            public function deleteForPeriod(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                \DateTimeImmutable $periodStart,
                \DateTimeImmutable $periodEnd,
            ): int {
                return 0;
            }

            public function hasEntityPermission(
                int $userId,
                string $entityType,
                string $guard = 'web',
            ): bool {
                return true;
            }

            public function userExists(int $userId): bool
            {
                return User::where('id', $userId)->exists();
            }
        };
        $clock = new class implements ClockInterface
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2024-01-01T12:00:00Z');
            }
        };
        $periods = new class implements PeriodServiceInterface
        {
            public function getRange(string $periodType, \DateTimeImmutable $reference): array
            {
                return match ($periodType) {
                    'daily' => [$reference->setTime(
                        0,
                        0,
                        0,
                    ), $reference->setTime(
                        23,
                        59,
                        59,
                    )],
                    'monthly' => [$reference->modify('first day of this month')->setTime(0, 0, 0), $reference->modify('last day of this month')->setTime(
                        23,
                        59,
                        59,
                    )],
                    'yearly' => [$reference->setDate(
                        (int) $reference->format('Y'),
                        1,
                        1,
                    )->setTime(
                        0,
                        0,
                        0,
                    ), $reference->setDate(
                        (int) $reference->format('Y'),
                        12,
                        31,
                    )->setTime(
                        23,
                        59,
                        59,
                    )],
                    default => [$reference->setTime(
                        0,
                        0,
                        0,
                    ), $reference->setTime(
                        23,
                        59,
                        59,
                    )],
                };
            }
        };
        $logger = new class implements LogInterface
        {
            public array $logs = [];

            public function log(
                string $level,
                string $message,
                array $context = [],
            ): void {
                $this->logs[] = compact(
                    'level',
                    'message',
                    'context',
                );
            }
        };
        $this->service = new UniversalLimitService(
            $resolver,
            $entityLimitRepo,
            $entityLimitUsageRepo,
            $clock,
            $periods,
            $logger,
            app(BusinessReadRepositoryInterface::class),
            app(ContactReadRepositoryInterface::class),
        );
    }

    #[Test]
    public function product_guard_usage_records(): void
    {
        $user = User::factory()->create();
        // Ensure permission for web guard
        Permission::firstOrCreate(['name' => 'can_create_edit_product', 'guard_name' => 'web']);
        $user->givePermissionTo('can_create_edit_product');
        EntityLimit::create([
            'permission_name' => 'can_create_edit_product',
            'entity_type' => 'product',
            'metric_type' => 'count',
            'period_type' => 'daily',
            'limit_value' => 10,
        ]);

        // Generic recordUsage replaces specialized recordProductCreation
        $this->service->recordUsage(
            $user->id,
            'product',
            'count',
            null,
            'web',
            1,
        );
        $result = $this->service->recordUsage(
            $user->id,
            'product',
            'count',
            null,
            'web',
            1,
        );
        $this->assertTrue($result);
    }
}
