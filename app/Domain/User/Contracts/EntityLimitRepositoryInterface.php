<?php

namespace App\Domain\User\Contracts;

/**
 * Repository contract for reading entity limits based on permissions.
 */
interface EntityLimitRepositoryInterface
{
    /**
     * Get active entity limits for the given permission names.
     * Should return a collection of items with keys: permission_name, entity_type, metric_type, period_type, limit_value.
     *
     * @param array<int,string> $permissionNames
     * @return array<int, array{
     *   permission_name:string,
     *   entity_type:string,
     *   metric_type:string,
     *   period_type:string|null,
     *   limit_value:int
     * }>
     */
    public function getActiveByPermissions(array $permissionNames): array;

    /**
     * Get permission names configured for an entity type. When includeInactive=true,
     * returns permission names regardless of is_active flag.
     *
     * @return array<int,string>
     */
    public function getPermissionNamesForEntity(string $entityType, bool $includeInactive = true): array;

    /**
     * Optimized variant that returns only limits for the specific filters.
     */
    public function getActiveByPermissionsFiltered(
        array $permissionNames,
        string $entityType,
        string $metricType,
        string $periodType
    ): array;

    /**
     * Get all defined entity types.
     * @return array<int,string>
     */
    public function getAllEntityTypes(): array;

    /**
     * Get all defined metric types.
     * @return array<int,string>
     */
    public function getAllMetricTypes(): array;

    /**
     * Get all defined period types.
     * @return array<int,string>
     */
    public function getAllPeriodTypes(): array;
}
