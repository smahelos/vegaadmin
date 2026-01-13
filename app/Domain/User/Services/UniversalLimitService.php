<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\PermissionLimitResolverInterface;
use App\Domain\User\Contracts\EntityLimitRepositoryInterface;
use App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\Shared\Time\Contracts\PeriodServiceInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;

/**
 * Universal Limit Service - Permission-based System
 * 
 * Core business logic for entity limit checking and enforcement
 * across all application entities (invoices, clients, suppliers, etc.)
 * 
 * Updated to use userId instead of User objects for clean Domain layer architecture.
 * This version uses PermissionLimitResolver for real-time permission-based limits.
 */
class UniversalLimitService implements \App\Domain\User\Contracts\UniversalLimitServiceInterface
{
    /**
     * Permission Limit Resolver instance
     */
    private PermissionLimitResolverInterface $permissionResolver;
    private EntityLimitRepositoryInterface $entityLimitRepository;
    private EntityLimitUsageRepositoryInterface $entityLimitUsageRepository;
    private ClockInterface $clock;
    private PeriodServiceInterface $periodService;
    private readonly LogInterface $logger;

    /**
     * Constructor
     */
    public function __construct(
        PermissionLimitResolverInterface $permissionResolver,
        EntityLimitRepositoryInterface $entityLimitRepository,
        EntityLimitUsageRepositoryInterface $entityLimitUsageRepository,
        ClockInterface $clock,
        PeriodServiceInterface $periodService,
        LogInterface $logger
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->entityLimitRepository = $entityLimitRepository;
        $this->entityLimitUsageRepository = $entityLimitUsageRepository;
        $this->clock = $clock;
        $this->periodService = $periodService;
        $this->logger = $logger;
    }

    /**
     * Check if user can perform action on entity
     */
    public function checkLimit(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        string $periodType = 'daily',
        int|float $value = 1
    ): array {
        // Get user's effective limit from permissions
        $userLimit = $this->permissionResolver->getUserLimit($userId, $entityType, $metricType, $periodType);
        
        if ($userLimit === 0) {
            return [
                'allowed' => false,
                'reason' => 'no_permission',
                'entity_type' => $entityType,
                'metric_type' => $metricType,
                'period_type' => $periodType,
                'limit' => 0,
                'current_usage' => 0,
                'remaining' => 0
            ];
        }
        
        $currentUsage = $this->getCurrentUsage($userId, $entityType, $metricType, $periodType);
        $newUsage = $currentUsage + $value;
        $allowed = $newUsage <= $userLimit;

        return [
            'allowed' => $allowed,
            'current_usage' => $currentUsage,
            'limit' => $userLimit,
            'remaining' => max(0, $userLimit - $currentUsage),
            'entity_type' => $entityType,
            'metric_type' => $metricType,
            'period_type' => $periodType,
            'reason' => $allowed ? 'within_limit' : 'limit_exceeded'
        ];
    }
    
    /**
     * Record usage when entity is created
     */
    public function recordUsage(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        ?string $periodType = null,
        string $guard = 'web',
        int|float $value = 1
    ): bool {
        if ($periodType === null) {
            $periodType = $this->getBestPeriodType($userId, $entityType, $metricType);

        }

        // Handle anonymous users
        if (!$userId) {
            $this->logger->log('debug', 'UELS: Cannot record usage for anonymous user');
            return false;
        }

        // Check if user has permission for this entity type (using Infrastructure layer)
        if (!$this->entityLimitUsageRepository->hasEntityPermission($userId, $entityType, $guard)) {
            $this->logger->log(
                'warning', 
                'UELS: Attempted to record usage without permission', 
                [
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'guard' => $guard
                ]
            );
            return false;
        }

        try {
            [$periodStart, $periodEnd] = $this->calculatePeriodRange($periodType);

            $success = $this->entityLimitUsageRepository->incrementOrCreateCurrentPeriod(
                $userId, 
                $entityType, 
                $metricType, 
                $periodType,
                $periodStart, 
                $periodEnd, 
                $value
            );

            return $success;

        } catch (\Exception $e) {
            $this->logger->log(
                'error', 
                'UELS: Failed to record usage: ' . $e->getMessage(), 
                [
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'error' => $e->getMessage()
                ]
            );

            return false;
        }
    }

    /**
     * Get usage statistics for user/entity/metric/period
     */
    public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array
    {
        $userLimit = $this->permissionResolver->getUserLimit($userId, $entityType, $metricType, $periodType);
        $currentUsage = $this->getCurrentUsage($userId, $entityType, $metricType, $periodType);
        [$periodStart, $periodEnd] = $this->calculatePeriodRange($periodType);

        return [
            'entity_type' => $entityType,
            'metric_type' => $metricType,
            'period_type' => $periodType,
            'limit' => $userLimit,
            'current_usage' => $currentUsage,
            'remaining' => max(0, $userLimit - $currentUsage),
            'usage_percentage' => $userLimit > 0 ? round(($currentUsage / $userLimit) * 100, 2) : 0,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'can_create' => $currentUsage < $userLimit
        ];
    }

    /**
     * Get all usage statistics for user across all entities
     */
    public function getAllUsageStatistics(?int $userId): array
    {
        $result = [];

        foreach ($this->entityLimitRepository->getAllEntityTypes() as $entityType) {
            foreach (array_keys($this->entityLimitRepository->getAllMetricTypes()) as $metricType) {
                foreach (array_keys($this->entityLimitRepository->getAllPeriodTypes()) as $periodType) {
                    $stats = $this->getUsageStatistics($userId, $entityType, $metricType, $periodType);
                    if ($stats['limit'] > 0) {
                        $result[$entityType][$metricType][$periodType] = $stats;
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * Reset usage for given combination
     */
    public function resetUsage(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): bool
    {
        if (!$userId) {
            return false;
        }

        try {
            [$periodStart, $periodEnd] = $this->calculatePeriodRange($periodType);
            
            $deletedRows = $this->entityLimitUsageRepository->deleteForPeriod(
                $userId, 
                $entityType, 
                $metricType, 
                $periodType, 
                $periodStart, 
                $periodEnd
            );

            return $deletedRows > 0;

        } catch (\Exception $e) {
            $this->logger->log(
                'error', 
                'UELS: Failed to reset usage: ' . $e->getMessage(), 
                [
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'error' => $e->getMessage()
                ]
            );

            return false;
        }
    }

    /**
     * Check if user can create specific entity type
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool
    {
        return $this->permissionResolver->canUserCreateEntity($userId, $entityType);
    }

    /**
     * Get rich entity limit info for widgets/dashboards
     */
    public function getEntityLimitInfo(?int $userId, string $entityType): array
    {
        $limits = [];
        $usages = [];
        $info = [
            'entity_type' => $entityType,
            'can_create' => $this->canUserCreateEntity($userId, $entityType),
            'limits' => [],
            'usage' => [],
            'best_period' => null,
            'user_permissions' => $this->permissionResolver->getUserPermissionLimits($userId, $entityType)
        ];

        if (!$info['can_create']) {
            return $info;
        }

        // Get limits and usage for all metric/period combinations
        foreach (array_keys($this->entityLimitRepository->getAllMetricTypes()) as $metricType) {
            foreach (array_keys($this->entityLimitRepository->getAllPeriodTypes()) as $periodType) {
                $limit = $this->permissionResolver->getUserLimit($userId, $entityType, $metricType, $periodType);
                
                if ($limit > 0) {
                    $usage = $this->getCurrentUsage($userId, $entityType, $metricType, $periodType);
                    $usages[$metricType][$periodType]['usage'] = $this->getUsageStatistics($userId, $entityType, $metricType, $periodType);
                    
                    // Track best (largest) period type for this entity
                    if (!$info['best_period'] || $this->isPeriodLarger($periodType, $info['best_period'])) {
                        $info['best_period'] = $periodType;
                    }
                    $limits[$metricType][$periodType]['limit'] = $limit;
                    $limits[$metricType][$periodType]['usage'] = $this->getUsageStatistics($userId, $entityType, $metricType, $periodType);
                    $limits[$metricType][$periodType]['remaining'] = max(0, $limit - $usage);
                    $limits[$metricType][$periodType]['percentage'] = min(100, ($usage / $limit) * 100);
                    $limits[$metricType][$periodType]['can_create'] = $usage < $limit;
                }
            }
        }
        
        $info = [
            'entity_type' => $entityType,
            'can_create' => $this->canUserCreateEntity($userId, $entityType),
            'limits' => $limits,
            'usage' => $usages,
            'best_period' => $info['best_period'],
            'user_permissions' => $this->permissionResolver->getUserPermissionLimits($userId, $entityType)
        ];

        return $info;
    }

    /**
     * Get current usage for user/entity/metric/period
     */
    private function getCurrentUsage(?int $userId, string $entityType, string $metricType, string $periodType): int|float
    {
        if (!$userId) {
            return 0;
        }

        try {
            [$periodStart, $periodEnd] = $this->calculatePeriodRange($periodType);
            
            return $this->entityLimitUsageRepository->getCurrentValue(
                $userId, 
                $entityType, 
                $metricType, 
                $periodType, 
                $periodStart, 
                $periodEnd
            );

        } catch (\Exception $e) {
            $this->logger->log(
                'error', 
                'UELS: Failed to get current usage: ' . $e->getMessage(), 
                [
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'error' => $e->getMessage()
                ]
            );

            return 0;
        }
    }

    /**
     * Calculate period start/end dates
     */
    private function calculatePeriodRange(string $periodType): array
    {
        $now = $this->clock->now();
        return $this->periodService->getRange($periodType, $now);
    }

    /**
     * Get the best (largest) period type allowed for user & entity
     */
    public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string
    {
        // Pick the period that yields the highest effective monthly capacity
        // using calculateEffectiveLimit() for normalization.
        $bestPeriod = 'daily'; // default fallback
        $bestEffective = -1.0;

        foreach (array_keys($this->entityLimitRepository->getAllPeriodTypes()) as $periodType) {
            $limit = $this->permissionResolver->getUserLimit($userId, $entityType, $metricType, $periodType);
            if ($limit <= 0) {
                continue;
            }

            $effective = $this->calculateEffectiveLimit($limit, $periodType);

            // Prefer higher effective capacity; on ties, prefer the larger period window
            if (
                $effective > $bestEffective ||
                ($effective === $bestEffective && $this->isPeriodLarger($periodType, $bestPeriod))
            ) {
                $bestEffective = $effective;
                $bestPeriod = $periodType;
            }
        }

        return $bestPeriod;
    }

    /**
     * Calculate effective limit normalized to monthly basis
     * 
     * @param int $limit Original limit value
     * @param string $period Period type (daily, weekly, monthly, yearly, lifetime)
     * @return float Effective limit normalized to monthly basis
     */
    private function calculateEffectiveLimit(int $limit, string $period): float
    {
        return match($period) {
            'daily' => $limit * 30,
            'weekly' => ($limit * 4) + 2,
            'monthly' => $limit * 1,
            'yearly' => $limit / 12,
            'lifetime' => $limit * 1,
            default => 0
        };
    }

    /**
     * Helper to check if period A is larger than period B
     */
    private function isPeriodLarger(string $periodA, string $periodB): bool
    {
        $periodOrder = ['hourly' => 1, 'daily' => 2, 'weekly' => 3, 'monthly' => 4, 'yearly' => 5, 'lifetime' => 6];
        
        return ($periodOrder[$periodA] ?? 0) > ($periodOrder[$periodB] ?? 0);
    }
}
