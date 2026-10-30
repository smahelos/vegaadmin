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
     * @param  int|null  $userId  User ID (null for anonymous users)
     * @param  string  $entityType  Entity type (invoice, contact, etc.)
     * @param  string  $metricType  Metric type (count, value, size)
     * @param  string  $periodType  Period type (daily, monthly, etc.)
     * @param  int|float  $value  Value to check against limit
     * @return array Limit check result
     */
    public function checkLimit(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        string $periodType = 'daily',
        int|float $value = 1,
    ): array;

    /**
     * Record usage for the given entity/metric/period (auto-creates or updates usage row).
     * Returns true on successful persistence (or safe no-op), false if a persistence error occurred or permission missing.
     *
     * @param  int|null  $userId  User ID (null for anonymous users)
     * @param  string  $periodType  Period type
     * @param  string  $guard  Guard name for permission checking
     * @return bool True on success, false on failure
     */
    public function recordUsage(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        ?string $periodType = null,
        string $guard = 'web',
        int|float $value = 1,
    ): bool;

    /**
     * Get usage statistics (never null). Unified shape consumed by UI.
     */
    public function getUsageStatistics(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        string $periodType = 'daily',
    ): array;

    /**
     * Get the best (largest) period type allowed for user & entity.
     */
    public function getBestPeriodType(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
    ): string;

    /**
     * Rich aggregated info for widgets/dashboards.
     *
     * @return array Entity limit information
     */
    public function getEntityLimitInfo(?int $userId, string $entityType): array;

    /**
     * Check if user can create specific entity type (any metric/period).
     *
     * @return bool True if user can create entity
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool;
}
