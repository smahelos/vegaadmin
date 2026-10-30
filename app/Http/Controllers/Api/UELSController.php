<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\User\Contracts\UELSApplicationServiceInterface;
use App\Domain\User\Enums\EntityType;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * API Controller for Universal Entity Limit System (UELS) - Permission-based
 * Provides frontend endpoints for retrieving entity limit and usage data
 */
final class UELSController extends Controller
{
    public function __construct(private readonly UELSApplicationServiceInterface $uels) {}

    /**
     * Get UELS data for specific entity type
     *
     * @param  string  $entity  The entity type (business, contact, product, invoice)
     */
    public function getEntityData(Request $request, string $entity): JsonResponse
    {
        $user = $request->user();
        $metricType = EntityType::tryFrom($entity) === null ? 'count' : 'active';

        $validEntities = [
            'contact',
            'business',
            'product',
            'invoice',
        ];
        if (! in_array($entity, $validEntities)) {
            return response()->json(['error' => 'Invalid entity type'], 400);
        }

        try {
            // Get comprehensive limit info for the entity
            $entityInfo = $this->uels->getEntityLimitInfo($user?->id, $entity);
            $canCreateEntity = (bool) ($entityInfo['can_create'] ?? ($entityInfo['has_permission'] ?? false));

            if (! $canCreateEntity) {
                return response()->json([
                    'entity' => $entity,
                    'current_usage' => 0,
                    'limit' => 0,
                    'remaining_count' => 0,
                    'percentage_used' => 0,
                    'is_at_limit' => true,
                    'status' => 'no_permission',
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Anonymous',
                    'message' => 'No permission to create this entity type',
                    'best_stats_period' => null,
                    'overview' => [
                        'currentUsage' => 0,
                        'limit' => 0,
                    ],
                    'currentUsage' => [
                        'today' => 0,
                        'thisWeek' => 0,
                        'thisMonth' => 0,
                        'total' => 0,
                    ],
                    'recentActivity' => [],
                    'permissions' => [],
                ]);
            }

            // Normalize permissions to array whether it's a Collection or plain array
            $activePermissions = is_array($entityInfo['user_permissions'])
                ? $entityInfo['user_permissions']
                : ($entityInfo['user_permissions']?->toArray() ?? []);
            if (! empty($activePermissions)) {
                $limitPeriod['daily'] = $this->findKey('daily', $activePermissions);
                $limitPeriod['weekly'] = $this->findKey('weekly', $activePermissions);
                $limitPeriod['monthly'] = $this->findKey('monthly', $activePermissions);
                $limitPeriod['yearly'] = $this->findKey('yearly', $activePermissions);
                $limitPeriod['lifetime'] = $this->findKey('lifetime', $activePermissions);
            }
            $dailyStats = [];
            $weeklyStats = [];
            $monthlyStats = [];
            $yearlyStats = [];
            $lifetimeStats = [];

            // Select primary stats from first available limit (prefer daily, then weekly, then monthly, then yearly, then lifetime)
            $primaryStats = null;
            // $allStats = [$dailyStats, $weeklyStats, $monthlyStats, $yearlyStats, $lifetimeStats];

            // Filter allStats just to have only those within limitPeriod item returning true
            if (isset($limitPeriod['daily']) && $limitPeriod['daily']) {
                $dailyStats = $this->uels->getUsageStatistics(
                    $user?->id,
                    $entity,
                    $metricType,
                    'daily',
                ) ?? [];
            }
            if (isset($limitPeriod['weekly']) && $limitPeriod['weekly']) {
                $weeklyStats = $this->uels->getUsageStatistics(
                    $user?->id,
                    $entity,
                    $metricType,
                    'weekly',
                ) ?? [];
            }
            if (isset($limitPeriod['monthly']) && $limitPeriod['monthly']) {
                $monthlyStats = $this->uels->getUsageStatistics(
                    $user?->id,
                    $entity,
                    $metricType,
                    'monthly',
                ) ?? [];
            }
            if (isset($limitPeriod['yearly']) && $limitPeriod['yearly']) {
                $yearlyStats = $this->uels->getUsageStatistics(
                    $user?->id,
                    $entity,
                    $metricType,
                    'yearly',
                ) ?? [];
            }
            if (isset($limitPeriod['lifetime']) && $limitPeriod['lifetime']) {
                $lifetimeStats = $this->uels->getUsageStatistics(
                    $user?->id,
                    $entity,
                    $metricType,
                    'lifetime',
                ) ?? [];
            }

            // Find the stat with the highest effective limit (normalized to monthly basis)
            $bestStats = null;
            $highestEffectiveLimit = 0;

            $statsWithPeriods = [
                'daily' => $dailyStats,
                'weekly' => $weeklyStats,
                'monthly' => $monthlyStats,
                'yearly' => $yearlyStats,
                'lifetime' => $lifetimeStats,
            ];

            $bestStatsPeriod = null;
            foreach ($statsWithPeriods as $period => $stats) {
                if ($stats && $stats['limit'] > 0) {
                    $effectiveLimit = $this->calculateEffectiveLimit($stats['limit'], $period);
                    Log::info('UELS: Calculating effective limit', [
                        'period' => $period,
                        'original_limit' => $stats['limit'],
                        'effective_limit' => $effectiveLimit,
                    ]);

                    if ($effectiveLimit > $highestEffectiveLimit) {
                        $highestEffectiveLimit = $effectiveLimit;
                        $bestStats = $stats;
                        $bestStatsPeriod = $period;
                    }
                }
            }

            $primaryStats = $bestStats;

            // If no limit > 0 found, use the first available stat as fallback
            if (! $primaryStats) {
                $primaryStats = $dailyStats ?: $weeklyStats ?: $monthlyStats ?: $yearlyStats ?: $lifetimeStats;
            }

            if (! $primaryStats) {
                // Fallback - user has permissions but no limits configured
                return response()->json([
                    'entity' => $entity,
                    'current_usage' => 0,
                    'limit' => -1,
                    'remaining_count' => -1,
                    'percentage_used' => 0,
                    'is_at_limit' => false,
                    'status' => 'unlimited',
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Anonymous',
                    'message' => 'Unlimited access for this entity type',
                    'best_stats_period' => $bestStatsPeriod,
                    'overview' => [
                        'currentUsage' => 0,
                        'limit' => -1,
                    ],
                    'currentUsage' => [
                        'today' => 0,
                        'thisWeek' => 0,
                        'thisMonth' => 0,
                        'total' => 0,
                    ],
                    'recentActivity' => [],
                    'permissions' => is_array($entityInfo['user_permissions'])
                        ? $entityInfo['user_permissions']
                        : ($entityInfo['user_permissions']?->toArray() ?? []),
                ]);
            }

            $currentUsage = $primaryStats['current_usage'] ?? 0;
            $limit = $primaryStats['limit'] ?? 0;
            $remaining = $primaryStats['remaining'] ?? max(0, $limit - $currentUsage);
            $percentageUsed = $primaryStats['usage_percentage'] ?? 0;
            $primaryCanCreate = $primaryStats['can_create'] ?? ($limit > $currentUsage);

            return response()->json([
                'entity' => $entity,
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'remaining_count' => $remaining,
                'percentage_used' => $percentageUsed,
                'is_at_limit' => ! $primaryCanCreate,
                'status' => $this->getStatusLevel($percentageUsed, $primaryCanCreate),
                'period_start' => $primaryStats['period_start'] ?? null,
                'period_end' => $primaryStats['period_end'] ?? null,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Anonymous',
                'message' => $this->getStatusMessage(
                    $percentageUsed,
                    $primaryStats['can_create'],
                    $entity,
                ),
                'best_stats_period' => $bestStatsPeriod,
                // Modal data structure
                'overview' => [
                    'currentUsage' => $currentUsage,
                    'limit' => $limit,
                ],
                'currentUsage' => [
                    'today' => $dailyStats['current_usage'] ?? 0,
                    'thisWeek' => $weeklyStats['current_usage'] ?? 0,
                    'thisMonth' => $monthlyStats['current_usage'] ?? 0,
                    'thisYear' => $yearlyStats['current_usage'] ?? 0,
                    'lifetime' => $lifetimeStats['current_usage'] ?? 0,
                    'total' => $currentUsage,
                ],
                'recentActivity' => $this->getRecentActivity($user, $entity),
                'permissions' => is_array($entityInfo['user_permissions'])
                    ? $entityInfo['user_permissions']
                    : ($entityInfo['user_permissions']?->toArray() ?? []),
                'all_limits' => $entityInfo['limits'],
            ]);

        } catch (Throwable $e) {
            Log::error('Loading UELS data failed', ['exception' => $e]);

            return response()->json(['message' => __('entity_limits.modal.error')], 500);
        }
    }

    /**
     * Summary of findKey
     */
    public function findKey(mixed $keySearch, mixed $array): bool
    {
        foreach ($array as $key => $item) {
            if ($key == $keySearch) {
                return true;
            } elseif (is_array($item) && $this->findKey($keySearch, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get comprehensive UELS data for all entities
     */
    public function getAllEntitiesData(Request $request): JsonResponse
    {
        $user = $request->user();
        $entities = [
            'contact',
            'business',
            'product',
            'invoice',
        ];
        $data = [];
        foreach ($entities as $entity) {
            $response = $this->getEntityData($request, $entity);
            if ($response->getStatusCode() !== 200) {
                return $response;
            }
            $data[$entity] = $response->getData(true);
        }

        return response()->json([
            'entities' => $data,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get status level based on usage percentage and permission
     */
    private function getStatusLevel(float $percentageUsed, bool $canCreate): string
    {
        if (! $canCreate) {
            return 'danger';
        }

        if ($percentageUsed >= 90) {
            return 'danger';
        } elseif ($percentageUsed >= 75) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Get status message based on usage and permission
     */
    private function getStatusMessage(
        float $percentageUsed,
        bool $canCreate,
        string $entity,
    ): string {
        if (! $canCreate) {
            return "You have reached your {$entity} limit for this period.";
        }

        if ($percentageUsed >= 90) {
            return "You are close to your {$entity} limit.";
        } elseif ($percentageUsed >= 75) {
            return "You have used most of your {$entity} allocation.";
        } else {
            return "You are within your {$entity} limits.";
        }
    }

    /**
     * Get recent activity for an entity (placeholder for future implementation)
     */
    private function getRecentActivity(?Authenticatable $user, string $entity): array
    {
        // TODO: Implement recent activity tracking
        // For now, return empty array
        return [];
    }

    /**
     * Calculate effective limit normalized to monthly basis
     *
     * @param  int  $limit  Original limit value
     * @param  string  $period  Period type (daily, weekly, monthly, yearly, lifetime)
     * @return float Effective limit normalized to monthly basis
     */
    private function calculateEffectiveLimit(int $limit, string $period): float
    {
        return match ($period) {
            'daily' => $limit * 30,
            'weekly' => ($limit * 4) + 2,
            'monthly' => $limit * 1,
            'yearly' => $limit / 12,
            'lifetime' => $limit * 1,
            default => 0,
        };
    }
}
