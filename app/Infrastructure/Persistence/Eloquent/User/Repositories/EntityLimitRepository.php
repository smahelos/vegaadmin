<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Models\EntityLimit;

class EntityLimitRepository implements EntityLimitRepositoryInterface
{
    public function getActiveByPermissions(array $permissionNames): array
    {
        if (empty($permissionNames)) {
            return [];
        }

        $rows = DB::table('entity_limits')
            ->select('permission_name', 'entity_type', 'metric_type', 'period_type', 'limit_value')
            ->whereIn('permission_name', $permissionNames)
            ->where('is_active', 1)
            ->get();

        return $rows->map(fn ($row) => [
            'permission_name' => $row->permission_name,
            'entity_type' => $row->entity_type,
            'metric_type' => $row->metric_type,
            'period_type' => $row->period_type,
            'limit_value' => (int) $row->limit_value,
        ])->all();
    }

    public function getPermissionNamesForEntity(string $entityType, bool $includeInactive = true): array
    {
        $query = DB::table('entity_limits')
            ->select('permission_name')
            ->where('entity_type', $entityType)
            ->whereNotNull('permission_name')
            ->distinct();

        if (!$includeInactive) {
            $query->where('is_active', 1);
        }

        return $query->pluck('permission_name')->filter()->values()->all();
    }

    public function getActiveByPermissionsFiltered(
        array $permissionNames,
        string $entityType,
        string $metricType,
        string $periodType
    ): array {
        if (empty($permissionNames)) {
            return [];
        }

        $rows = DB::table('entity_limits')
            ->select('permission_name', 'entity_type', 'metric_type', 'period_type', 'limit_value')
            ->whereIn('permission_name', $permissionNames)
            ->where('entity_type', $entityType)
            ->where('metric_type', $metricType)
            ->where('period_type', $periodType)
            ->where('is_active', 1)
            ->get();

        return $rows->map(fn ($row) => [
            'permission_name' => $row->permission_name,
            'entity_type' => $row->entity_type,
            'metric_type' => $row->metric_type,
            'period_type' => $row->period_type,
            'limit_value' => (int) $row->limit_value,
        ])->all();
    }

    /**
     * Get all defined entity types.
     * @return array
     */
    public function getAllEntityTypes(): array
    {
        return EntityLimit::ENTITY_TYPES;
    }

    /**
     * Get all defined entity types.
     * @return array
     */
    public function getAllMetricTypes(): array
    {
        return EntityLimit::METRIC_TYPES;
    }

    /**
     * Get all defined entity types.
     * @return array
     */
    public function getAllPeriodTypes(): array
    {
        return EntityLimit::PERIOD_TYPES;
    }
}
