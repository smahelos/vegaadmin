<?php

namespace App\Domain\User\Contracts;

/**
 * Repository contract for persisting and querying entity limit usage rows.
 * 
 * Extended to support permission checking and user validation
 * to move Eloquent logic from Domain to Infrastructure layer.
 */
interface EntityLimitUsageRepositoryInterface
{
    public function existsCurrentPeriod(int $userId, string $entityType, string $metricType, string $periodType, \DateTimeImmutable $now): bool;

    public function insertUsage(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        float|int $currentValue = 0
    ): void;

    /**
     * Get current usage value for a specific user/entity/metric within an exact period range.
     */
    public function getCurrentValue(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): int|float;

    /**
     * Atomically increment usage for the current period, or create a row if it doesn't exist.
     * Returns true on success.
     */
    public function incrementOrCreateCurrentPeriod(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        int|float $value
    ): bool;

    /**
     * Delete usage rows for the given period. Returns number of deleted rows.
     */
    public function deleteForPeriod(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): int;

    /**
     * Check if user has permission to create/access the given entity type.
     * This moves User loading and permission checking to Infrastructure layer.
     * 
     * @param int $userId User ID
     * @param string $entityType Entity type (invoice, client, etc.)
     * @param string $guard Guard name for permission checking
     * @return bool True if user has permission
     */
    public function hasEntityPermission(int $userId, string $entityType, string $guard = 'web'): bool;

    /**
     * Check if user exists.
     * 
     * @param int $userId User ID
     * @return bool True if user exists
     */
    public function userExists(int $userId): bool;
}
