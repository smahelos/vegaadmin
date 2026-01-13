<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\PermissionLimitResolverInterface;
use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;

/**
 * Permission Limit Resolver Service
 * 
 * Handles real-time calculation of entity limits based on user permissions.
 * Always returns the highest limit available to the user from all their permissions.
 * 
 * Updated to use userId instead of User objects for clean Domain layer architecture.
 */
class PermissionLimitResolver implements PermissionLimitResolverInterface
{
    /**
     * Repository for reading entity limits (nullable for lazy resolution in non-Laravel unit tests).
     */
    private EntityLimitRepositoryInterface $limitRepo;

    /**
     * User permission service for getting user permissions without Eloquent dependency
     */
    private UserPermissionServiceInterface $userPermissionService;

    /**
     * Cache service abstraction to avoid direct framework dependency.
     */
    private CacheServiceInterface $cacheService;

    /**
     * Cache key prefix for user limits
     */
    private const CACHE_PREFIX = 'uels_user_limits_';

    /**
     * Cache tag used for group invalidation of UELS limits.
     */
    private const CACHE_TAG = 'uels_user_limits';

    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Special permission name for anonymous users (kept here to avoid model dependency).
     */
    private const ANONYMOUS_PERMISSION = '__anonymous_user__';

    /**
     * Constructor with optional DI to keep unit tests simple.
     * When not resolved via the container, falls back to resolving the repo on demand.
     */
    public function __construct(
        EntityLimitRepositoryInterface $limitRepo,
        UserPermissionServiceInterface $userPermissionService,
        CacheServiceInterface $cacheService
    ) {
        $this->limitRepo = $limitRepo;
        $this->userPermissionService = $userPermissionService;
        $this->cacheService = $cacheService;
    }

    /**
     * Lazy accessor for the repository to support environments where container bindings are not loaded.
     */
    private function repo(): EntityLimitRepositoryInterface
    {
        return $this->limitRepo;
    }

    /**
     * Lazy accessor for the user permission service
     */
    private function userPermissionService(): UserPermissionServiceInterface
    {
        return $this->userPermissionService;
    }

    /**
     * Lazy accessor for cache service to support environments where container bindings are not loaded.
     */
    private function cache(): CacheServiceInterface
    {
        return $this->cacheService;
    }

    /**
     * Get the effective limit for a user and entity type
     * 
     * @param int|null $userId The user ID (null for anonymous)
     * @param string $entityType The entity type (invoice, client, etc.)
     * @param string $metricType The metric type (count, value, size)
     * @param string $periodType The period type (daily, monthly, etc.)
     * @return int The highest limit value, or 0 if no permission
     */
    public function getUserLimit(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): int
    {
        // Handle anonymous users early
        if (!$userId) {
            return $this->getAnonymousLimit($entityType, $metricType, $periodType);
        }

        // Try cache first
        $cacheKey = $this->getCacheKey($userId, $entityType, $metricType, $periodType);
        return $this->cache()->remember(
            $cacheKey,
            function () use ($userId, $entityType, $metricType, $periodType) {
                return $this->calculateUserLimit($userId, $entityType, $metricType, $periodType);
            },
            self::CACHE_TTL,
            [self::CACHE_TAG, "user:{$userId}", "entity:{$entityType}"]
        );
    }

    /**
     * Get limit for anonymous users
     */
    private function getAnonymousLimit(string $entityType, string $metricType, string $periodType): int
    {
        $limits = $this->repo()->getActiveByPermissions([self::ANONYMOUS_PERMISSION]);
        foreach ($limits as $row) {
            if (
                ($row['entity_type'] ?? null) === $entityType &&
                ($row['metric_type'] ?? null) === $metricType &&
                ($row['period_type'] ?? null) === $periodType
            ) {
                return (int) $row['limit_value'];
            }
        }
        return 0;
    }

    /**
     * Calculate the highest limit for a user based on all their permissions
     */
    private function calculateUserLimit(int $userId, string $entityType, string $metricType, string $periodType): int
    {
        // Check if user exists first
        if (!$this->userPermissionService()->userExists($userId)) {
            return 0;
        }

        // Get all permissions for the user (via roles and direct permissions)
        $permissionNames = $this->userPermissionService()->getUserPermissions($userId);
        
        if (empty($permissionNames)) {
            return 0;
        }

        // Use repository to load limits for these permissions and filter by entity/metric/period
        $rows = $this->repo()->getActiveByPermissionsFiltered($permissionNames, $entityType, $metricType, $periodType);
        if (empty($rows)) {
            return 0;
        }
        $highest = 0;
        foreach ($rows as $row) {
            $val = (int) ($row['limit_value'] ?? 0);
            if ($val > $highest) {
                $highest = $val;
            }
        }
        return $highest;
    }

    /**
     * Get all limits for a user across all entity types
     * 
     * @param int|null $userId User ID
     * @return array Format: ['entity_type' => ['metric_type' => ['period_type' => limit_value]]]
     */
    public function getAllUserLimits(?int $userId): array
    {
        $result = [];

        foreach ($this->repo()->getAllEntityTypes() as $entityType) {
            foreach (array_keys($this->repo()->getAllMetricTypes()) as $metricType) {
                foreach (array_keys($this->repo()->getAllPeriodTypes()) as $periodType) {
                    $limit = $this->getUserLimit($userId, $entityType, $metricType, $periodType);
                    if ($limit > 0) {
                        $result[$entityType][$metricType][$periodType] = $limit;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get limits for specific entity type across all metrics and periods
     */
    public function getEntityLimits(?int $userId, string $entityType): array
    {
        $result = [];

        foreach (array_keys($this->repo()->getAllMetricTypes()) as $metricType) {
            foreach (array_keys($this->repo()->getAllPeriodTypes()) as $periodType) {
                $limit = $this->getUserLimit($userId, $entityType, $metricType, $periodType);
                if ($limit > 0) {
                    $result[$metricType][$periodType] = $limit;
                }
            }
        }

        return $result;
    }

    /**
     * Clear cache for a specific user
     */
    public function clearUserCache(int $userId): void
    {
        // Clear all possible combinations
        foreach ($this->repo()->getAllEntityTypes() as $entityType) {
            foreach (array_keys($this->repo()->getAllMetricTypes()) as $metricType) {
                foreach (array_keys($this->repo()->getAllPeriodTypes()) as $periodType) {
                    $cacheKey = $this->getCacheKey($userId, $entityType, $metricType, $periodType);
                    $this->cache()->forget($cacheKey);
                }
            }
        }
    }

    /**
     * Clear cache for all users (use sparingly)
     */
    public function clearAllCache(): void
    {
        // Use cache tags to invalidate all UELS-related entries
        $this->cache()->invalidateTags([self::CACHE_TAG]);
    }

    /**
     * Get cache key for user limit
     */
    private function getCacheKey(int $userId, string $entityType, string $metricType, string $periodType): string
    {
        return self::CACHE_PREFIX . "{$userId}_{$entityType}_{$metricType}_{$periodType}";
    }

    /**
     * Check if user can create entity (has any permission that allows it)
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
     * @return bool True if user can create entity
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool
    {
        if (!$userId) {
            return false;
        }

        // Check for any limit > 0 for any metric/period combination
        foreach (array_keys($this->repo()->getAllMetricTypes()) as $metricType) {
            foreach (array_keys($this->repo()->getAllPeriodTypes()) as $periodType) {
                if ($this->getUserLimit($userId, $entityType, $metricType, $periodType) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get user's permissions with their corresponding limits for an entity
     * 
     * @param int|null $userId User ID
     * @param string $entityType Entity type
    * @return array<int, array{permission:string, limits: array<string, array<string, int>>}>
     */
    public function getUserPermissionLimits(?int $userId, string $entityType): array
    {
        if (!$userId) {
            // Return anonymous limits
            $anonymousLimits = [];
            foreach (array_keys($this->repo()->getAllMetricTypes()) as $metricType) {
                foreach (array_keys($this->repo()->getAllPeriodTypes()) as $periodType) {
                    $limit = $this->getAnonymousLimit($entityType, $metricType, $periodType);
                    if ($limit > 0) {
                        $anonymousLimits[$metricType][$periodType] = $limit;
                    }
                }
            }
            return [[
                'permission' => self::ANONYMOUS_PERMISSION,
                'limits' => $anonymousLimits,
            ]];
        }

        $permissionNames = $this->userPermissionService()->getUserPermissions($userId);
        $result = [];

        if (empty($permissionNames)) {
            return $result;
        }

        // Preload all limits for user's permissions in one go using repository
        $allLimits = array_values(array_filter(
            $this->repo()->getActiveByPermissions($permissionNames),
            fn (array $row) => ($row['entity_type'] ?? null) === $entityType
        ));

        foreach ($permissionNames as $permissionName) {
            $limits = [];
            $permLimits = array_values(array_filter(
                $allLimits,
                fn (array $row) => ($row['permission_name'] ?? null) === $permissionName
            ));

            foreach (array_keys($this->repo()->getAllMetricTypes()) as $metricType) {
                foreach (array_keys($this->repo()->getAllPeriodTypes()) as $periodType) {
                    $found = null;
                    foreach ($permLimits as $row) {
                        if (($row['metric_type'] ?? null) === $metricType && ($row['period_type'] ?? null) === $periodType) {
                            $found = $row;
                            break;
                        }
                    }
                    if ($found !== null) {
                        $limits[$metricType][$periodType] = (int) $found['limit_value'];
                    }
                }
            }

            if (!empty($limits)) {
                $result[] = [
                    'permission' => $permissionName,
                    'limits' => $limits,
                ];
            }
        }

        return $result;
    }
}
