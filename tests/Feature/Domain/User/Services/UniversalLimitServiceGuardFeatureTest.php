<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Services\UniversalLimitService;
use App\Domain\User\Contracts\PermissionLimitResolverInterface;
use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface;
use App\Models\User;
use App\Models\EntityLimit;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;
use Spatie\Permission\Models\Permission;

class UniversalLimitServiceGuardFeatureTest extends TestCase
{
    use RefreshDatabaseWithData; // Service-level behavior needing seeded permissions

    private UniversalLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Use minimal resolver implementation to allow recording logic
        $resolver = new class implements PermissionLimitResolverInterface {
            public function getUserLimit(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): int { return 5; }
            public function getAllUserLimits(?int $userId): array { return []; }
            public function getEntityLimits(?int $userId, string $entityType): array { return []; }
            public function clearUserCache(int $userId): void {}
            public function clearAllCache(): void {}
            public function canUserCreateEntity(?int $userId, string $entityType): bool { return true; }
            public function getUserPermissionLimits(?int $userId, string $entityType): array { return []; }
        };
        // Inject stub repositories that do nothing but satisfy type hints
        $entityLimitRepo = new class implements EntityLimitRepositoryInterface {
            public function getActiveByPermissions(array $permissionNames): array { return []; }
            public function getPermissionNamesForEntity(string $entityType, bool $includeInactive = true): array {
                // Simulate configured permission names for given entity so permission check can pass
                return $entityType === 'invoice' ? ['can_create_edit_invoice'] : [];
            }
            public function getActiveByPermissionsFiltered(
                array $permissionNames,
                string $entityType,
                string $metricType,
                string $periodType
            ): array { return []; }
            public function getAllEntityTypes(): array { return ['invoice', 'client', 'supplier', 'product', 'expense']; }
            public function getAllMetricTypes(): array { return ['count' => true]; }
            public function getAllPeriodTypes(): array { return [
                'daily' => true,
                'monthly' => true,
                'yearly' => true,
                'lifetime' => true,
            ]; }
        };
        $entityLimitUsageRepo = new class implements EntityLimitUsageRepositoryInterface {
            public function existsCurrentPeriod(int $userId, string $entityType, string $metricType, string $periodType, DateTimeImmutable $now): bool { return false; }
            public function insertUsage(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                DateTimeImmutable $periodStart,
                DateTimeImmutable $periodEnd,
                float|int $currentValue = 0
            ): void {}

            /**
             * Get current usage value for a specific user/entity/metric within an exact period range.
             */
            public function getCurrentValue(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                DateTimeImmutable $periodStart,
                DateTimeImmutable $periodEnd
            ): int|float { return 0; }

            /**
             * Atomically increment usage for the current period, or create a row if it doesn't exist.
             * Returns true on success.
             */
            public function incrementOrCreateCurrentPeriod(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                DateTimeImmutable $periodStart,
                DateTimeImmutable $periodEnd,
                int|float $value
            ): bool { return true; }

            /**
             * Delete usage rows for the given period. Returns number of deleted rows.
             */
            public function deleteForPeriod(
                int $userId,
                string $entityType,
                string $metricType,
                string $periodType,
                DateTimeImmutable $periodStart,
                DateTimeImmutable $periodEnd
            ): int { return 0; }

            /**
             * Check if user exists.
             * 
             * @param int $userId User ID
             * @return bool True if user exists
             */
            public function userExists(int $userId): bool { return true; }

            /**
             * Check if user has permission to create/access the given entity type.
             * This moves User loading and permission checking to Infrastructure layer.
             * 
             * @param int $userId User ID
             * @param string $entityType Entity type (invoice, client, etc.)
             * @param string $guard Guard name for permission checking
             * @return bool True if user has permission
             */
            public function hasEntityPermission(int $userId, string $entityType, string $guard = 'web'): bool { return true; }
        };
        $clock = new class implements \App\Domain\Shared\Time\Contracts\ClockInterface {
            public function now(): DateTimeImmutable {
                return new DateTimeImmutable('now');
            }
        };
        $periodService = new class implements \App\Domain\Shared\Time\Contracts\PeriodServiceInterface {
            public function getRange(string $periodType, \DateTimeImmutable $reference): array {
                // Simplified period ranges for testing
                return match ($periodType) {
                    'daily' => [new DateTimeImmutable($reference->format('Y-m-d 00:00:00')), new DateTimeImmutable($reference->format('Y-m-d 23:59:59'))],
                    'monthly' => [new DateTimeImmutable($reference->format('Y-m-01 00:00:00')), new DateTimeImmutable($reference->format('Y-m-t 23:59:59'))],
                    'yearly' => [new DateTimeImmutable($reference->format('Y-01-01 00:00:00')), new DateTimeImmutable($reference->format('Y-12-31 23:59:59'))],
                    'lifetime' => [new DateTimeImmutable('1970-01-01 00:00:00'), new DateTimeImmutable('2100-12-31 23:59:59')],
                    default => throw new \InvalidArgumentException("Unsupported period type: $periodType"),
                };
            }

        };
        $logger = new class implements \App\Domain\Shared\Log\Contracts\LogInterface {
            public function log(string $level, string $message, array $context = []): void {}
        };
        $this->service = new UniversalLimitService(
            $resolver, 
            $entityLimitRepo, 
            $entityLimitUsageRepo,
            $clock,
            $periodService,
            $logger
        );
    }

    #[Test]
    public function guard_parameter_is_string_not_numeric_when_recording_usage(): void
    {
        $user = User::factory()->create();
        // Create permission for web guard (service logic operates on default user guard)
        Permission::firstOrCreate(['name' => 'can_create_edit_invoice', 'guard_name' => 'web']);
        $user->givePermissionTo('can_create_edit_invoice');
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'daily',
            'limit_value' => 10,
        ]);

        // Generic recordUsage replaces specialized recordInvoiceCreation
    $this->service->recordUsage($user->id, 'invoice', 'count', null, 'web', 1);
    $result = $this->service->recordUsage($user->id, 'invoice', 'count', null, 'web', 1);
        $this->assertTrue($result, 'Recording usage with string guard should succeed');
        $this->assertFalse(is_numeric('web'), 'Guard "web" should not be numeric');
    }
}
