<?php

namespace App\Domain\User\Contracts;

/**
 * Interface for resolving user limits based on permissions.
 *
 * Updated to use userId instead of User objects for clean Domain layer architecture.
 */
interface PermissionLimitResolverInterface
{
    /**
     * Get the effective limit for a user and entity type
     *
     * @param  int|null  $userId  The user ID (null for anonymous)
     * @param  string  $entityType  The entity type (invoice, contact, etc.)
     * @param  string  $metricType  The metric type (count, value, size)
     * @param  string  $periodType  The period type (daily, monthly, etc.)
     * @return int The highest limit value, or 0 if no permission
     */
    public function getUserLimit(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        string $periodType = 'daily',
    ): int;

    /**
     * Check if user can create entity (has any permission that allows it)
     *
     * @return bool True if user can create entity
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool;

    /**
     * Get user's permissions with their corresponding limits for an entity
     *
     * @return array<int, array{permission:string, limits: array<string, array<string, int>>}>
     */
    public function getUserPermissionLimits(?int $userId, string $entityType): array;
}
