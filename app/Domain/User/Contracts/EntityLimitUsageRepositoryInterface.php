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
    /**
     * Get current usage value for a specific user/entity/metric within an exact period range.
     */
    public function getCurrentValue(
        int $userId,
        string $entityType,
        string $metricType,
        string $periodType,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
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
        int|float $value,
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
        \DateTimeImmutable $periodEnd,
    ): int;

    /**
     * Check if user has permission to create/access the given entity type.
     * This moves User loading and permission checking to Infrastructure layer.
     *
     * @param  string  $entityType  Entity type (invoice, contact, etc.)
     * @param  string  $guard  Guard name for permission checking
     * @return bool True if user has permission
     */
    public function hasEntityPermission(
        int $userId,
        string $entityType,
        string $guard = 'web',
    ): bool;
}
