<?php

namespace App\Http\Controllers\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class UELSDashboardController
 * Universal Entity Limit System Dashboard Controller - Permission-based System
 */
class UELSDashboardController extends CrudController
{
    /**
     * Show the UELS dashboard
     */
    public function index(Request $request): View
    {
        // Check permissions
        $user = backpack_user();
        if (!$user || !$user->hasPermissionTo('can_configure_system', 'backpack')) {
            abort(403, 'Unauthorized action.');
        }

        $limitService = app(UniversalLimitService::class);

        // Get overview statistics
        $stats = $this->getOverviewStats();

        // Get limit violations
        $violations = $this->getLimitViolations($limitService);

        // Get usage trends (last 30 days)
        $trends = $this->getUsageTrends();

        // Get active limits breakdown
        $limitsBreakdown = $this->getLimitsBreakdown();

        // Get recent activity
        $recentActivity = $this->getRecentActivity();

        return view(
            'admin.uels-dashboard',
            compact(
                'stats',
                'violations',
                'trends',
                'limitsBreakdown',
                'recentActivity',
            ),
        );
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(): array
    {
        $now = CarbonImmutable::now();
        $activeLimitQuery = EntityLimit::where('is_active', true);
        $usersWithUsageQuery = EntityLimitUsage::distinct('user_id');
        $usersWithUsageQuery->whereNotNull('user_id');
        $todayQuery = EntityLimitUsage::where(
            'period_start',
            '>=',
            $now->startOfDay(),
        );
        $weekQuery = EntityLimitUsage::where(
            'period_start',
            '>=',
            $now->startOfWeek(),
        );
        $monthQuery = EntityLimitUsage::where(
            'period_start',
            '>=',
            $now->startOfMonth(),
        );
        $yearQuery = EntityLimitUsage::where(
            'period_start',
            '>=',
            $now->startOfYear(),
        );
        $lifetimeQuery = EntityLimitUsage::where(
            'period_end',
            '>=',
            '9999-12-31 23:59:59',
        );
        $permissionQuery = EntityLimit::distinct('permission_name');
        $anonymousQuery = EntityLimitUsage::whereNull('user_id');

        return [
            'total_limits' => EntityLimit::count(),
            'active_limits' => $activeLimitQuery->count(),
            'total_users_with_usage' => $usersWithUsageQuery->count('user_id'),
            'total_usage_records' => EntityLimitUsage::count(),
            'today_usage' => $todayQuery->sum('current_value'),
            'this_week_usage' => $weekQuery->sum('current_value'),
            'this_month_usage' => $monthQuery->sum('current_value'),
            'this_year_usage' => $yearQuery->sum('current_value'),
            'lifetime_usage' => $lifetimeQuery->sum('current_value'),
            'total_permissions_with_limits' => $permissionQuery->count('permission_name'),
            'anonymous_usage_records' => $anonymousQuery->count(),
        ];
    }

    /**
     * Get current limit violations
     */
    private function getLimitViolations(UniversalLimitService $limitService): array
    {
        $violations = [];
        $query = User::take(50);
        $users = $query->get(); // Limit to 50 users for performance
        $entityTypes = EntityLimit::ENTITY_TYPES;
        $metricTypes = array_keys(EntityLimit::METRIC_TYPES);
        $periodTypes = array_keys(EntityLimit::PERIOD_TYPES);

        foreach ($users as $user) {
            foreach ($entityTypes as $entityType) {
                foreach ($metricTypes as $metricType) {
                    foreach ($periodTypes as $periodType) {
                        try {
                            $check = $limitService->checkLimit(
                                $user->id,
                                $entityType,
                                $metricType,
                                $periodType,
                            );
                            if (
                                isset($check['current_usage'])
                                && isset($check['limit'])
                                && $check['current_usage'] > 0
                            ) {
                                $check['usage_percentage'] = (int) $check['current_usage'] / (int) $check['limit'] * 100;
                            } else {
                                $check['usage_percentage'] = 0;

                            }
                            if (!$check['allowed'] && $check['reason'] === 'limit_exceeded') {
                                $violations[] = [
                                    'user' => $user,
                                    'entity_type' => $entityType,
                                    'metric_type' => $metricType,
                                    'period_type' => $periodType,
                                    'current_usage' => $check['current_usage'] ?? 0,
                                    'limit_value' => $check['limit'] ?? 0,
                                    'percentage' => $check['usage_percentage'] ?? 0,
                                ];
                            }
                        } catch (\Exception $e) {
                            // Skip if there's an error checking this combination
                            continue;
                        }
                    }
                }
            }
        }

        $sorted = collect($violations);
        $sorted = $sorted->sortByDesc('percentage');
        $sorted = $sorted->take(20);
        $sorted = $sorted->values();

        return $sorted->all();
    }

    /**
     * Get usage trends for the last 30 days
     */
    private function getUsageTrends(): array
    {
        $now = CarbonImmutable::now();
        $thirtyDaysAgo = $now->subDays(30);

        $query = DB::table('entity_limit_usage');
        $query->select(
            DB::raw('DATE(period_start) as date'),
            'entity_type',
            'metric_type',
            'period_type',
            DB::raw('SUM(current_value) as total_usage'),
            DB::raw('COUNT(*) as usage_count'),
        );
        $query->where(
            'period_start',
            '>=',
            $thirtyDaysAgo,
        );
        $query->groupBy(
            'date',
            'entity_type',
            'metric_type',
            'period_type',
        );
        $query->orderBy('date');
        $usage = $query->get();
        $trends = $usage->groupBy('entity_type');

        return $trends->toArray();
    }

    /**
     * Get limits breakdown by entity type
     */
    private function getLimitsBreakdown(): array
    {
        $query = EntityLimit::select(
            'entity_type',
            'metric_type',
            'period_type',
            'permission_name',
        );
        $query->where('is_active', true);
        $limits = $query->get();
        $grouped = $limits->groupBy(['entity_type', 'metric_type']);
        $breakdown = $grouped->map(
            function ($metricType) {
                return $metricType->map(
                    function ($periods) {
                        $byPeriod = $periods->groupBy('period_type');

                        return $byPeriod->map(
                            function ($items) {
                                $permissions = $items->pluck('permission_name');
                                $permissions = $permissions->unique();
                                $permissions = $permissions->values();

                                return [
                                    'count' => $items->count(),
                                    'permissions' => $permissions->toArray(),
                                ];
                            },
                        );
                    },
                );
            },
        );

        return $breakdown->toArray();
    }

    /**
     * Get recent activity (last 50 usage updates)
     */
    private function getRecentActivity(): array
    {
        $query = EntityLimitUsage::with('user');
        $query->orderBy('last_reset_at', 'desc');
        $query->take(50);
        $usages = $query->get();
        $activity = $usages->map(
            function ($usage) {
                return [
                    'user_name' => $usage->user->name ?? ($usage->user_id ? 'Unknown User' : 'Anonymous'),
                    'user_id' => $usage->user_id,
                    'entity_type' => $usage->entity_type,
                    'metric_type' => $usage->metric_type,
                    'period_type' => $usage->period_type,
                    'current_value' => $usage->current_value,
                    'period_start' => $usage->period_start,
                    'period_end' => $usage->period_end,
                    'last_updated_at' => $usage->last_reset_at,
                ];
            },
        );

        return $activity->toArray();
    }

    /**
     * Get limit analysis for specific user (AJAX endpoint)
     */
    public function getUserLimitAnalysis(Request $request): JsonResponse
    {
        $user = backpack_user();
        if (!$user || !$user->hasPermissionTo('can_configure_system', 'backpack')) {
            $responseFactory = response();

            return $responseFactory->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $limitService = app(UniversalLimitService::class);

        $analysis = [];
        $entityTypes = EntityLimit::ENTITY_TYPES;
        $metricTypes = array_keys(EntityLimit::METRIC_TYPES);
        $periodTypes = array_keys(EntityLimit::PERIOD_TYPES);

        foreach ($entityTypes as $entityType) {
            $analysis[$entityType] = [];

            foreach ($metricTypes as $metricType) {
                $analysis[$entityType][$metricType] = [];

                foreach ($periodTypes as $periodType) {
                    $check = $limitService->checkLimit(
                        $user->id,
                        $entityType,
                        $metricType,
                        $periodType,
                    );
                    if (isset($check['current_usage']) && isset($check['limit']) && $check['current_usage'] > 0) {
                        $check['usage_percentage'] = (int) $check['current_usage'] / (int) $check['limit'] * 100;
                    } else {
                        $check['usage_percentage'] = 0;

                    }

                    Log::debug(
                        "UELS: User {$user->id} limit check",
                        [
                            'entity_type' => $entityType,
                            'metric_type' => $metricType,
                            'period_type' => $periodType,
                            'allowed' => $check['allowed'],
                            'current_usage' => $check['current_usage'] ?? 0,
                            'limit_value' => $check['limit'] ?? null,
                            'usage_percentage' => $check['usage_percentage'] ?? 0,
                            'reason' => $check['reason'] ?? null,
                        ],
                    );
                    $analysis[$entityType][$metricType][$periodType] = [
                        'allowed' => $check['allowed'],
                        'current_usage' => $check['current_usage'] ?? 0,
                        'limit_value' => $check['limit'] ?? null,
                        'percentage' => $check['usage_percentage'] ?? 0,
                        'reason' => $check['reason'] ?? null,
                        'permission_name' => $check['limit']['permission_name'] ?? null,
                    ];
                }
            }
        }

        $responseFactory = response();

        return $responseFactory->json(
            [
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                ]),
                'analysis' => $analysis,
            ],
        );
    }

    /**
     * Bulk reset usage (AJAX endpoint)
     */
    public function bulkResetUsage(Request $request): JsonResponse
    {
        $user = backpack_user();
        if (!$user || !$user->hasPermissionTo('can_configure_system', 'backpack')) {
            $responseFactory = response();

            return $responseFactory->json(['error' => 'Unauthorized'], 403);
        }

        Log::info(
            'request 1',
            [
                'entity_type' => $request->entity_type,
                'metric_type' => $request->metric_type,
                'period_type' => $request->period_type,
                'user_ids' => $request->user_ids,
                'reset_all_users' => $request->reset_all_users,
                'include_anonymous' => $request->include_anonymous,
            ],
        );

        $request->validate(
            [
                'entity_type' => 'required|in:' . implode(',', EntityLimit::ENTITY_TYPES),
                'metric_type' => 'required|in:' . implode(
                    ',',
                    array_keys(EntityLimit::METRIC_TYPES),
                ),
                'period_type' => 'required|in:' . implode(
                    ',',
                    array_keys(EntityLimit::PERIOD_TYPES),
                ),
                // 'user_ids' => 'nullable|array',
                // 'user_ids.*' => 'exists:users,id',
                'reset_all_users' => 'accepted|string',
                'include_anonymous' => 'accepted|string',
            ],
        );

        Log::info(
            'request 2',
            [
                'entity_type' => $request->entity_type,
                'metric_type' => $request->metric_type,
                'period_type' => $request->period_type,
                'user_ids' => $request->user_ids,
                'reset_all_users' => $request->reset_all_users,
                'include_anonymous' => $request->include_anonymous,
            ],
        );

        $limitService = app(UniversalLimitService::class);
        $successCount = 0;
        $errorCount = 0;

        if ($request->reset_all_users) {
            // Reset for all users
            $users = User::all();
        } else {
            // Reset for specific users
            $query = User::whereIn('id', $request->user_ids ?? []);
            $users = $query->get();
        }

        // Reset for authenticated users
        foreach ($users as $user) {
            try {
                if (
                    $limitService->resetUsage(
                        $user,
                        $request->entity_type,
                        $request->metric_type,
                        $request->period_type,
                    )
                ) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        // Reset for anonymous users if requested
        if ($request->include_anonymous) {
            try {
                if (
                    $limitService->resetUsage(
                        null,
                        $request->entity_type,
                        $request->metric_type,
                        $request->period_type,
                    )
                ) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        Log::info(
            'UELS: Bulk reset completed',
            [
                'entity_type' => $request->entity_type,
                'metric_type' => $request->metric_type,
                'period_type' => $request->period_type,
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ],
        );

        $responseFactory = response();

        return $responseFactory->json(
            [
                'success' => true,
                'message' => __('entity_limits.dashboard.reset.completed', ['success' => $successCount, 'failed' => $errorCount]),
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ],
        );
    }

    /**
     * Export usage data (CSV)
     */
    public function exportUsageData(Request $request): StreamedResponse
    {
        $user = backpack_user();
        if (!$user || !$user->hasPermissionTo('can_configure_system', 'backpack')) {
            abort(403, 'Unauthorized action.');
        }

        $query = EntityLimitUsage::with('user');

        // Apply filters if provided
        if ($request->has('entity_type') && $request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('metric_type') && $request->metric_type) {
            $query->where('metric_type', $request->metric_type);
        }

        if ($request->has('period_type') && $request->period_type) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where(
                'period_start',
                '>=',
                $request->date_from,
            );
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where(
                'period_end',
                '<=',
                $request->date_to,
            );
        }

        // Filter by user type
        if ($request->has('user_type')) {
            if ($request->user_type === 'authenticated') {
                $query->whereNotNull('user_id');
            } elseif ($request->user_type === 'anonymous') {
                $query->whereNull('user_id');
            }
        }

        $query->orderBy('last_reset_at', 'desc');
        $usages = $query->get();

        $now = CarbonImmutable::now();
        $filename = 'entity_usage_export_' . $now->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($usages): void {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv(
                $file,
                [
                    'User ID',
                    'User Name',
                    'User Email',
                    'User Type',
                    'Entity Type',
                    'Metric Type',
                    'Period Type',
                    'Period Start',
                    'Period End',
                    'Current Value',
                    'Last Updated',
                ],
            );

            // CSV data
            foreach ($usages as $usage) {
                fputcsv(
                    $file,
                    [
                        $usage->user_id ?? 'N/A',
                        $usage->user->name ?? 'N/A',
                        $usage->user->email ?? 'N/A',
                        $usage->user_id ? 'Authenticated' : 'Anonymous',
                        $usage->entity_type ?? 'N/A',
                        $usage->metric_type ?? 'N/A',
                        $usage->period_type ?? 'N/A',
                        $usage->period_start,
                        $usage->period_end,
                        $usage->current_value,
                        $usage->last_reset_at,
                    ],
                );
            }

            fclose($file);
        };

        $responseFactory = response();

        return $responseFactory->stream(
            $callback,
            200,
            $headers,
        );
    }
}
