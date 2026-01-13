<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface;
use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Infrastructure implementation of EntityLimitUsageRepositoryInterface.
 * 
 * Extended to handle User loading and permission checking,
 * moving Eloquent logic from Domain to Infrastructure layer.
 */
class EntityLimitUsageRepository implements EntityLimitUsageRepositoryInterface
{
    /**
     * Repository for reading entity limits
     */
    private EntityLimitRepositoryInterface $entityLimitRepository;

    public function __construct(EntityLimitRepositoryInterface $entityLimitRepository)
    {
        $this->entityLimitRepository = $entityLimitRepository;
    }
    public function existsCurrentPeriod(int $userId, string $entityType, string $metricType, string $periodType, \DateTimeImmutable $now): bool
    {
        $nowCarbon = CarbonImmutable::instance($now);
        return DB::table('entity_limit_usage')
            ->where('user_id', $userId)
            ->where('entity_type', $entityType)
            ->where('metric_type', $metricType)
            ->where('period_type', $periodType)
            ->where('period_start', '<=', $nowCarbon)
            ->where('period_end', '>', $nowCarbon)
            ->exists();
    }

    public function insertUsage(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        float|int $currentValue = 0
    ): void {
        $start = CarbonImmutable::instance($periodStart);
        $end = CarbonImmutable::instance($periodEnd);
        DB::table('entity_limit_usage')->insert([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'metric_type' => $metricType,
            'period_type' => $periodType,
            'period_start' => $start,
            'period_end' => $end,
            'current_value' => $currentValue,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getCurrentValue(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): int|float {
        $now = CarbonImmutable::now();
        $row = DB::table('entity_limit_usage')
            ->where('user_id', $userId)
            ->where('entity_type', $entityType)
            ->where('metric_type', $metricType)
            ->where('period_type', $periodType)
            ->where('period_start', '<=', $now)
            ->where('period_end', '>', $now)
            ->first();

            Log::debug('EntityLimitUsageRepository: Fetched current value', [
            'user_id' => $userId,
            'entity_type' => $entityType,
            'metric_type' => $metricType,
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'current_value' => $row ? (float) $row->current_value : 0
        ]);

        return $row ? (float) $row->current_value : 0;
    }

    public function incrementOrCreateCurrentPeriod(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        int|float $value
    ): bool {
        $now = CarbonImmutable::now();
        // Try update first
        $updated = DB::table('entity_limit_usage')
            ->where('user_id', $userId)
            ->where('entity_type', $entityType)
            ->where('metric_type', $metricType)
            ->where('period_type', $periodType)
            ->where('period_start', '<=', $now)
            ->where('period_end', '>', $now)
            ->update([
                'current_value' => DB::raw('current_value + ' . ((float)$value)),
                'updated_at' => now(),
            ]);

        Log::debug('UELS: Increment usage attempt', [
            'user_id' => $userId,
            'entity_type' => $entityType,
            'metric_type' => $metricType,
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'increment_value' => $value,
            'rows_updated' => $updated
        ]);

         // If updated rows > 0, return true
        if ($updated > 0) {
            return true;
        }

        // Create if not exists
        $inserted = DB::table('entity_limit_usage')->insert([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'metric_type' => $metricType,
            'period_type' => $periodType,
            'period_start' => CarbonImmutable::instance($periodStart),
            'period_end' => CarbonImmutable::instance($periodEnd),
            'current_value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (bool) $inserted;
    }

    public function deleteForPeriod(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): int {
        return DB::table('entity_limit_usage')
            ->where('user_id', $userId)
            ->where('entity_type', $entityType)
            ->where('metric_type', $metricType)
            ->where('period_type', $periodType)
            ->where('period_start', CarbonImmutable::instance($periodStart))
            ->where('period_end', CarbonImmutable::instance($periodEnd))
            ->delete();
    }

    /**
     * Check if user has permission to create/access the given entity type.
     * This moves User loading and permission checking to Infrastructure layer.
     */
    public function hasEntityPermission(int $userId, string $entityType, string $guard = 'web'): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            Log::debug('EntityLimitUsageRepository: User not found for permission check', [
                'user_id' => $userId,
                'entity_type' => $entityType,
                'guard' => $guard
            ]);
            return false;
        }

        // Get all permissions that have limits configured for this entity type (include inactive)
        $permissionsForEntity = $this->entityLimitRepository->getPermissionNamesForEntity($entityType, true);
        
        if (empty($permissionsForEntity)) {
            Log::debug('EntityLimitUsageRepository: No permissions configured for entity type', [
                'entity_type' => $entityType,
                'user_id' => $userId
            ]);
            return false;
        }

        // Check if user has any of the permissions configured for this entity
        foreach ($permissionsForEntity as $permission) {
            if ($user->hasPermissionTo($permission, $guard)) {
                Log::debug('EntityLimitUsageRepository: User has permission for entity', [
                    'entity_type' => $entityType,
                    'user_id' => $userId,
                    'permission' => $permission,
                    'guard' => $guard
                ]);
                return true;
            }
        }

        Log::debug('EntityLimitUsageRepository: User has no permission for entity', [
            'entity_type' => $entityType,
            'user_id' => $userId,
            'checked_permissions' => $permissionsForEntity,
            'guard' => $guard
        ]);

        return false;
    }

    /**
     * Check if user exists.
     */
    public function userExists(int $userId): bool
    {
        $exists = User::where('id', $userId)->exists();
        
        Log::debug('EntityLimitUsageRepository: User existence check', [
            'user_id' => $userId,
            'exists' => $exists
        ]);

        return $exists;
    }
}
