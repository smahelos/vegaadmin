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
     * @param int|null $userId The user ID (null for anonymous)
     * @param string $entityType The entity type (invoice, client, etc.)
     * @param string $metricType The metric type (count, value, size)
     * @param string $periodType The period type (daily, monthly, etc.)
     * @return int The highest limit value, or 0 if no permission
     */
    public function getUserLimit(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): int;

    /**
     * Get all limits for a user across all entity types
     * 
     * @param int|null $userId User ID
     * @return array Format: ['entity_type' => ['metric_type' => ['period_type' => limit_value]]]
     */
    public function getAllUserLimits(?int $userId): array;

    /**
     * Get limits for specific entity type across all metrics and periods
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @return array Limits for entity
     */
    public function getEntityLimits(?int $userId, string $entityType): array;

    /**
     * Clear cache for a specific user
     * 
     * @param int $userId User ID
     * @return void
     */
    public function clearUserCache(int $userId): void;

    /**
     * Clear cache for all users (use sparingly)
     * 
     * @return void
     */
    public function clearAllCache(): void;

    /**
     * Check if user can create entity (has any permission that allows it)
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @return bool True if user can create entity
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool;

    /**
     * Get user's permissions with their corresponding limits for an entity
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @return array<int, array{permission:string, limits: array<string, array<string, int>>}>
     */
    public function getUserPermissionLimits(?int $userId, string $entityType): array;
}
