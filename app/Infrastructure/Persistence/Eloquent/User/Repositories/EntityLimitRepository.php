<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Models\EntityLimit;
use Illuminate\Support\Facades\DB;

class EntityLimitRepository implements EntityLimitRepositoryInterface
{
    public function getActiveByPermissions(array $permissionNames): array
    {
        if (empty($permissionNames)) {
            return [];
        }

        $query = DB::table('entity_limits');
        $query->select(
            'permission_name',
            'entity_type',
            'metric_type',
            'period_type',
            'limit_value',
        );
        $query->whereIn('permission_name', $permissionNames);
        $query->where('is_active', 1);
        $rows = $query->get();

        $limits = $rows->map(
            fn ($row) => [
                'permission_name' => $row->permission_name,
                'entity_type' => $row->entity_type,
                'metric_type' => $row->metric_type,
                'period_type' => $row->period_type,
                'limit_value' => (int) $row->limit_value,
            ],
        );

        return $limits->all();
    }

    public function getPermissionNamesForEntity(string $entityType, bool $includeInactive = true): array
    {
        $query = DB::table('entity_limits');
        $query->select('permission_name');
        $query->where('entity_type', $entityType);
        $query->whereNotNull('permission_name');
        $query->distinct();

        if (!$includeInactive) {
            $query->where('is_active', 1);
        }

        $names = $query->pluck('permission_name');
        $names = $names->filter();
        $names = $names->values();

        return $names->all();
    }

    public function getActiveByPermissionsFiltered(
        array $permissionNames,
        string $entityType,
        string $metricType,
        string $periodType,
    ): array {
        if (empty($permissionNames)) {
            return [];
        }

        $query = DB::table('entity_limits');
        $query->select(
            'permission_name',
            'entity_type',
            'metric_type',
            'period_type',
            'limit_value',
        );
        $query->whereIn('permission_name', $permissionNames);
        $query->where('entity_type', $entityType);
        $query->where('metric_type', $metricType);
        $query->where('period_type', $periodType);
        $query->where('is_active', 1);
        $rows = $query->get();

        $limits = $rows->map(
            fn ($row) => [
                'permission_name' => $row->permission_name,
                'entity_type' => $row->entity_type,
                'metric_type' => $row->metric_type,
                'period_type' => $row->period_type,
                'limit_value' => (int) $row->limit_value,
            ],
        );

        return $limits->all();
    }

    /**
     * Get all defined entity types.
     */
    public function getAllEntityTypes(): array
    {
        return EntityLimit::ENTITY_TYPES;
    }

    /**
     * Get all defined entity types.
     */
    public function getAllMetricTypes(): array
    {
        return EntityLimit::METRIC_TYPES;
    }

    /**
     * Get all defined entity types.
     */
    public function getAllPeriodTypes(): array
    {
        return EntityLimit::PERIOD_TYPES;
    }
}
