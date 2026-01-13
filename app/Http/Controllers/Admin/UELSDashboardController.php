<?php

namespace App\Http\Controllers\Admin;

use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use App\Models\User;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class UELSDashboardController
 * Universal Entity Limit System Dashboard Controller - Permission-based System
 * 
 * @package App\Http\Controllers\Admin
 */
class UELSDashboardController extends CrudController
{
    /**
     * Show the UELS dashboard
     */
    public function index(Request $request)
    {
        // Check permissions
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
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

        return view('admin.uels-dashboard', compact(
            'stats',
            'violations', 
            'trends',
            'limitsBreakdown',
            'recentActivity'
        ));
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(): array
    {
        return [
            'total_limits' => EntityLimit::count(),
            'active_limits' => EntityLimit::where('is_active', true)->count(),
            'total_users_with_usage' => EntityLimitUsage::distinct('user_id')->whereNotNull('user_id')->count('user_id'),
            'total_usage_records' => EntityLimitUsage::count(),
            'today_usage' => EntityLimitUsage::where('period_start', '>=', now()->startOfDay())->get()->sum('current_value'),
            'this_week_usage' => EntityLimitUsage::where('period_start', '>=', now()->startOfWeek())->get()->sum('current_value'),
            'this_month_usage' => EntityLimitUsage::where('period_start', '>=', now()->startOfMonth())->get()->sum('current_value'),
            'this_year_usage' => EntityLimitUsage::where('period_start', '>=', now()->startOfYear())->get()->sum('current_value'),
            'lifetime_usage' => EntityLimitUsage::where('period_end', '>=', '9999-12-31 23:59:59')->get()->sum('current_value'),
            'total_permissions_with_limits' => EntityLimit::distinct('permission_name')->count('permission_name'),
            'anonymous_usage_records' => EntityLimitUsage::whereNull('user_id')->count(),
        ];
    }

    /**
     * Get current limit violations
     */
    private function getLimitViolations(UniversalLimitService $limitService): array
    {
        $violations = [];
        $users = User::take(50)->get(); // Limit to 50 users for performance
        $entityTypes = EntityLimit::ENTITY_TYPES;
        $metricTypes = array_keys(EntityLimit::METRIC_TYPES);
        $periodTypes = array_keys(EntityLimit::PERIOD_TYPES);

        foreach ($users as $user) {
            foreach ($entityTypes as $entityType) {
                foreach ($metricTypes as $metricType) {
                    foreach ($periodTypes as $periodType) {
                        try {
                            $check = $limitService->checkLimit($user, $entityType, $metricType, $periodType);
                            if (isset($check['current_usage']) && isset($check['limit']) && $check['current_usage'] > 0) {
                                $check['usage_percentage'] = (int)$check['current_usage'] / (int)$check['limit'] * 100;
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

        return collect($violations)->sortByDesc('percentage')->take(20)->values()->all();
    }

    /**
     * Get usage trends for the last 30 days
     */
    private function getUsageTrends(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        $trends = DB::table('entity_limit_usage')
            ->select(
                DB::raw('DATE(period_start) as date'),
                'entity_type',
                'metric_type',
                'period_type',
                DB::raw('SUM(current_value) as total_usage'),
                DB::raw('COUNT(*) as usage_count')
            )
            ->where('period_start', '>=', $thirtyDaysAgo)
            ->groupBy('date', 'entity_type', 'metric_type', 'period_type')
            ->orderBy('date')
            ->get()
            ->groupBy('entity_type');

        return $trends->toArray();
    }

    /**
     * Get limits breakdown by entity type
     */
    private function getLimitsBreakdown(): array
    {
        return EntityLimit::select('entity_type', 'metric_type', 'period_type', 'permission_name')
            ->where('is_active', true)
            ->get()
            ->groupBy(['entity_type', 'metric_type'])
            ->map(function ($metricType) {
                return $metricType->map(function ($periods) {
                    return $periods->groupBy('period_type')->map(function ($items) {
                        return [
                            'count' => $items->count(),
                            'permissions' => $items->pluck('permission_name')->unique()->values()->toArray()
                        ];
                    });
                });
            })
            ->toArray();
    }

    /**
     * Get recent activity (last 50 usage updates)
     */
    private function getRecentActivity(): array
    {
        return EntityLimitUsage::with('user')
            ->orderBy('last_reset_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($usage) {
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
            })
            ->toArray();
    }

    /**
     * Get limit analysis for specific user (AJAX endpoint)
     */
    public function getUserLimitAnalysis(Request $request)
    {
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id'
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
                    $check = $limitService->checkLimit($user->id, $entityType, $metricType, $periodType);
                    if (isset($check['current_usage']) && isset($check['limit']) && $check['current_usage'] > 0) {
                        $check['usage_percentage'] = (int)$check['current_usage'] / (int)$check['limit'] * 100;
                    } else {
                        $check['usage_percentage'] = 0;

                    }

                    Log::debug(
                        "UELS: User {$user->id} limit check", [
                        'entity_type' => $entityType,
                        'metric_type' => $metricType,
                        'period_type' => $periodType,
                        'allowed' => $check['allowed'],
                        'current_usage' => $check['current_usage'] ?? 0,
                        'limit_value' => $check['limit'] ?? null,
                        'usage_percentage' => $check['usage_percentage'] ?? 0,
                        'reason' => $check['reason'] ?? null,
                    ]);
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

        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'analysis' => $analysis
        ]);
    }

    /**
     * Bulk reset usage (AJAX endpoint)
     */
    public function bulkResetUsage(Request $request)
    {
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        Log::info("request 1", [
            'entity_type' => $request->entity_type,
            'metric_type' => $request->metric_type,
            'period_type' => $request->period_type,
            'user_ids' => $request->user_ids,
            'reset_all_users' => $request->reset_all_users,
            'include_anonymous' => $request->include_anonymous
        ]);

        $request->validate([
            'entity_type' => 'required|in:' . implode(',', EntityLimit::ENTITY_TYPES),
            'metric_type' => 'required|in:' . implode(',', array_keys(EntityLimit::METRIC_TYPES)),
            'period_type' => 'required|in:' . implode(',', array_keys(EntityLimit::PERIOD_TYPES)),
            //'user_ids' => 'nullable|array',
            //'user_ids.*' => 'exists:users,id',
            'reset_all_users' => 'accepted|string',
            'include_anonymous' => 'accepted|string'
        ]);

        Log::info("request 2", [
            'entity_type' => $request->entity_type,
            'metric_type' => $request->metric_type,
            'period_type' => $request->period_type,
            'user_ids' => $request->user_ids,
            'reset_all_users' => $request->reset_all_users,
            'include_anonymous' => $request->include_anonymous
        ]);

        $limitService = app(UniversalLimitService::class);
        $successCount = 0;
        $errorCount = 0;

        if ($request->reset_all_users) {
            // Reset for all users
            $users = User::all();
        } else {
            // Reset for specific users
            $users = User::whereIn('id', $request->user_ids ?? [])->get();
        }

        // Reset for authenticated users
        foreach ($users as $user) {
            try {
                if ($limitService->resetUsage($user, $request->entity_type, $request->metric_type, $request->period_type)) {
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
                if ($limitService->resetUsage(null, $request->entity_type, $request->metric_type, $request->period_type)) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        Log::info("UELS: Bulk reset completed", [
            'entity_type' => $request->entity_type,
            'metric_type' => $request->metric_type,
            'period_type' => $request->period_type,
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);

        return response()->json([
            'success' => true,
            'message' => "Reset completed: {$successCount} successful, {$errorCount} failed",
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
    }

    /**
     * Export usage data (CSV)
     */
    public function exportUsageData(Request $request)
    {
        if(!backpack_user() || !backpack_user()->hasPermissionTo('can_configure_system', 'backpack')) {
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
            $query->where('period_start', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->where('period_end', '<=', $request->date_to);
        }

        // Filter by user type
        if ($request->has('user_type')) {
            if ($request->user_type === 'authenticated') {
                $query->whereNotNull('user_id');
            } elseif ($request->user_type === 'anonymous') {
                $query->whereNull('user_id');
            }
        }

        $usages = $query->orderBy('last_reset_at', 'desc')->get();

        $filename = 'entity_usage_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($usages) {
            $file = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($file, [
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
                'Last Updated'
            ]);
            
            // CSV data
            foreach ($usages as $usage) {
                fputcsv($file, [
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
                    $usage->last_reset_at
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
