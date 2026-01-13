<?php

namespace App\Domain\User\Contracts;

/**
 * Contract for UniversalLimitService (introduced to allow interface-based DI and guard enforcement).
 * 
 * Updated to use userId instead of User objects for clean Domain layer architecture.
 */
interface UniversalLimitServiceInterface
{
    /**
     * Check limit for given entity type and metric.
     * Returns standardized associative structure with keys: allowed, current_usage, limit, remaining, entity_type, metric_type, period_type, reason.
     * 
     * @param int|null $userId User ID (null for anonymous users)
     * @param string $entityType Entity type (invoice, client, etc.)
     * @param string $metricType Metric type (count, value, size)
     * @param string $periodType Period type (daily, monthly, etc.)
     * @param int|float $value Value to check against limit
     * @return array Limit check result
     */
    public function checkLimit(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily', int|float $value = 1): array;

    /**
     * Record usage for the given entity/metric/period (auto-creates or updates usage row).
     * Returns true on successful persistence (or safe no-op), false if a persistence error occurred or permission missing.
     * 
     * @param int|null $userId User ID (null for anonymous users)
     * @param string $entityType Entity type
     * @param string $metricType Metric type
     * @param string $periodType Period type
     * @param string $guard Guard name for permission checking
     * @param int|float $value Value to record
     * @return bool True on success, false on failure
     */
    public function recordUsage(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        ?string $periodType = null,
        string $guard = 'web',
        int|float $value = 1
    ): bool;

    /**
     * Reset usage for the given combination in the current period. Returns true if any records were deleted.
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @param string $metricType Metric type
     * @param string $periodType Period type
     * @return bool True if records were deleted
     */
    public function resetUsage(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): bool;

    /**
     * Get usage statistics (never null). Unified shape consumed by UI.
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @param string $metricType Metric type
     * @param string $periodType Period type
     * @return array Usage statistics
     */
    public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array;

    /**
     * Get the best (largest) period type allowed for user & entity.
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @param string $metricType Metric type
     * @return string Best period type
     */
    public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string;

    /**
     * Rich aggregated info for widgets/dashboards.
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @return array Entity limit information
     */
    public function getEntityLimitInfo(?int $userId, string $entityType): array;

    /**
     * Check if user can create specific entity type (any metric/period).
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @return bool True if user can create entity
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool;
}
