<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DatabaseMaintenanceLog;
use App\Models\PerformanceMetric;
use App\Models\ArchivePolicy;
use App\Models\DatabaseHealthMetric;
use App\Models\DatabaseHealthAlert;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DatabaseDashboardController extends CrudController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Get database overview statistics
        $databaseStats = $this->getDatabaseStats();
        
        // Get recent maintenance tasks
        $recentTasks = $this->getRecentTasksWithDetails();
        
        // Get performance trends with available metric types
        $performanceTrends = $this->getPerformanceTrends();
        $availableMetricTypes = $this->getAvailableMetricTypes();
        
        // Get archive statistics
        $archiveStats = $this->getArchiveStats();
        
        // Get health monitoring status
        $healthStatus = $this->getHealthStatus();

        // Removed user activity stats - moved to main dashboard
        
        return view('admin.database.dashboard', compact(
            'databaseStats',
            'recentTasks', 
            'performanceTrends',
            'availableMetricTypes',
            'archiveStats',
            'healthStatus'
        ));
    }

    /**
     * Get database size and table statistics
     */
    private function getDatabaseStats()
    {
        try {
            // Get table sizes from our monitoring view
            $tableSizes = DB::select("SELECT * FROM database_size_monitor ORDER BY size_mb DESC LIMIT 10");
            
            // Get total database size
            $totalSize = collect($tableSizes)->sum('size_mb');
            
            // Get total tables count
            $totalTables = count($tableSizes);
            
            return [
                'total_size_mb' => round($totalSize, 2),
                'total_tables' => $totalTables,
                'largest_tables' => $tableSizes
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get database stats: ' . $e->getMessage());
            return [
                'total_size_mb' => 0,
                'total_tables' => 0,
                'largest_tables' => []
            ];
        }
    }

    /**
     * Get performance trends over time
     */
    private function getPerformanceTrends()
    {
        // Get the most common metric type as default
        $defaultMetricType = PerformanceMetric::select('metric_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('metric_type')
            ->orderBy('count', 'desc')
            ->first();

        $metricType = $defaultMetricType ? $defaultMetricType->metric_type : 'query_time';
        
        return $this->getPerformanceTrendsForType($metricType, 30);
    }

    /**
     * Get available metric types from database
     */
    private function getAvailableMetricTypes()
    {
        try {
            $metricTypes = PerformanceMetric::select('metric_type')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('MAX(measured_at) as last_measured')
                ->selectRaw('MIN(measured_at) as first_measured')
                ->groupBy('metric_type')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function ($metric) {
                    return [
                        'type' => $metric->metric_type,
                        'count' => $metric->count,
                        'label' => $this->getMetricTypeLabel($metric->metric_type),
                        'last_measured' => $metric->last_measured,
                        'first_measured' => $metric->first_measured
                    ];
                });

            return $metricTypes;
        } catch (\Exception $e) {
            Log::error('Failed to get available metric types: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get human-readable label for metric type
     */
    private function getMetricTypeLabel($metricType)
    {
        $labels = [
            'query_time' => __('admin.database.metrics.query_time'),
            'connection_usage' => __('admin.database.metrics.connection_usage'),
            'memory_usage' => __('admin.database.metrics.memory_usage'),
            'disk_usage' => __('admin.database.metrics.disk_usage'),
            'cache_hit_rate' => __('admin.database.metrics.cache_hit_rate'),
            'slow_queries' => __('admin.database.metrics.slow_queries'),
            'lock_wait_time' => __('admin.database.metrics.lock_wait_time'),
            'table_size' => __('admin.database.metrics.table_size'),
            'index_usage' => __('admin.database.metrics.index_usage'),
        ];

        return $labels[$metricType] ?? ucfirst(str_replace('_', ' ', $metricType));
    }

    /**
     * Get performance trends filtered by metric type via AJAX
     */
    public function getPerformanceTrendsByType(Request $request)
    {
        $metricType = $request->input('metric_type', 'query_time');
        $days = $request->input('days', 30);

        try {
            $performanceTrends = $this->getPerformanceTrendsForType($metricType, $days);
            
            return response()->json([
                'success' => true,
                'data' => $performanceTrends
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get performance trends by type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.failed_to_load_trends'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get performance trends for specific metric type
     */
    private function getPerformanceTrendsForType($metricType, $days = 30)
    {
        try {
            // Get metrics for specific type
            $metrics = PerformanceMetric::where('metric_type', $metricType)
                ->where('measured_at', '>=', now()->subDays($days))
                ->orderBy('measured_at')
                ->get();

            $labels = [];
            $data = [];
            $avgValue = 0;
            $unit = 'units';

            if ($metrics->isNotEmpty()) {
                // Group metrics by date for better visualization
                $groupedMetrics = $metrics->groupBy(function ($metric) {
                    return $metric->measured_at->format('Y-m-d');
                });

                foreach ($groupedMetrics as $date => $dayMetrics) {
                    $labels[] = \Carbon\Carbon::parse($date)->format('M d');
                    
                    // Calculate average for the day
                    $dayAverage = $dayMetrics->avg('metric_value');
                    $data[] = round($dayAverage, 2);
                }

                if (!empty($data)) {
                    $avgValue = round(array_sum($data) / count($data), 2);
                }

                // Get unit from first metric
                $unit = $metrics->first()->metric_unit ?? 'units';
            } else {
                // Generate sample data if no metrics exist for this type
                Log::warning("No performance metrics found for type: {$metricType}, generating sample data");
                
                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $labels[] = $date->format('M d');
                    
                    // Generate different sample data based on metric type
                    $data[] = $this->generateSampleDataForMetricType($metricType);
                }
                
                $avgValue = array_sum($data) / count($data);
                $unit = $this->getDefaultUnitForMetricType($metricType);
            }

            return [
                'labels' => $labels,
                'data' => $data,
                'avg_value' => round($avgValue, 2),
                'metric_type' => $metricType,
                'metric_label' => $this->getMetricTypeLabel($metricType),
                'unit' => $unit,
                'total_metrics' => $metrics->count(),
                'days' => $days
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get performance trends for type: ' . $e->getMessage());
            
            // Return fallback data
            $labels = [];
            $data = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('M d');
                $data[] = $this->generateSampleDataForMetricType($metricType);
            }
            
            return [
                'labels' => $labels,
                'data' => $data,
                'avg_value' => round(array_sum($data) / count($data), 2),
                'metric_type' => $metricType,
                'metric_label' => $this->getMetricTypeLabel($metricType),
                'unit' => $this->getDefaultUnitForMetricType($metricType),
                'total_metrics' => 0,
                'days' => $days,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate sample data based on metric type
     */
    private function generateSampleDataForMetricType($metricType)
    {
        switch ($metricType) {
            case 'query_time':
                return rand(10, 150); // 10-150ms
            case 'connection_usage':
                return rand(5, 85); // 5-85%
            case 'memory_usage':
                return rand(30, 90); // 30-90%
            case 'disk_usage':
                return rand(40, 95); // 40-95%
            case 'cache_hit_rate':
                return rand(75, 99); // 75-99%
            case 'slow_queries':
                return rand(0, 10); // 0-10 slow queries
            case 'lock_wait_time':
                return rand(0, 500); // 0-500ms
            case 'table_size':
                return rand(1, 1000); // 1-1000 MB
            case 'index_usage':
                return rand(60, 95); // 60-95%
            default:
                return rand(1, 100);
        }
    }

    /**
     * Get default unit for metric type
     */
    private function getDefaultUnitForMetricType($metricType)
    {
        $units = [
            'query_time' => 'ms',
            'connection_usage' => '%',
            'memory_usage' => '%',
            'disk_usage' => '%',
            'cache_hit_rate' => '%',
            'slow_queries' => 'count',
            'lock_wait_time' => 'ms',
            'table_size' => 'MB',
            'index_usage' => '%',
        ];

        return $units[$metricType] ?? 'units';
    }

    /**
     * Get archive statistics
     */
    private function getArchiveStats()
    {
        try {
            $archivedInvoices = DB::table('invoices_archive')->count();
            $archivedProducts = DB::table('invoice_products_archive')->count();
            
            return [
                'archived_records' => $archivedInvoices + $archivedProducts,
                'archived_invoices' => $archivedInvoices,
                'archived_products' => $archivedProducts
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get archive stats: ' . $e->getMessage());
            return [
                'archived_records' => 0,
                'archived_invoices' => 0,
                'archived_products' => 0
            ];
        }
    }

    /**
     * Get database health monitoring status with detailed metrics
     */
    private function getHealthStatus()
    {
        try {
            // Get recent health metrics (last 24 hours)
            $recentMetrics = DatabaseHealthMetric::recent()
                ->get()
                ->keyBy('metric_name');

            // Get unresolved alerts
            $criticalAlerts = DatabaseHealthAlert::unresolved()->severity('critical')->count();
            $warningAlerts = DatabaseHealthAlert::unresolved()->severity('warning')->count();
            $infoAlerts = DatabaseHealthAlert::unresolved()->severity('info')->count();

            // Get recent alerts for display
            $recentAlerts = DatabaseHealthAlert::unresolved()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Calculate health score
            $healthScore = $this->calculateHealthScore($recentMetrics);

            // Get last check time
            $lastCheck = DatabaseHealthMetric::orderBy('measured_at', 'desc')->first()?->measured_at;

            // Get detailed metrics breakdown
            $detailedMetrics = $this->getDetailedHealthMetrics($recentMetrics);

            // Get historical trend (last 7 days)
            $healthTrend = $this->getHealthTrend();

            return [
                'health_score' => $healthScore,
                'critical_alerts' => $criticalAlerts,
                'warning_alerts' => $warningAlerts,
                'info_alerts' => $infoAlerts,
                'last_check' => $lastCheck,
                'recent_metrics' => $recentMetrics,
                'recent_alerts' => $recentAlerts,
                'detailed_metrics' => $detailedMetrics,
                'health_trend' => $healthTrend
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get health status: ' . $e->getMessage());
            return [
                'health_score' => 0,
                'critical_alerts' => 0,
                'warning_alerts' => 0,
                'info_alerts' => 0,
                'last_check' => null,
                'recent_metrics' => collect([]),
                'recent_alerts' => collect([]),
                'detailed_metrics' => [],
                'health_trend' => []
            ];
        }
    }

    /**
     * Get detailed breakdown of health metrics
     */
    private function getDetailedHealthMetrics($recentMetrics)
    {
        $metrics = [];

        // Connection metrics
        if (isset($recentMetrics['connection_usage'])) {
            $metric = $recentMetrics['connection_usage'];
            $metrics['connections'] = [
                'name' => __('admin.database.metrics.connection_usage'),
                'value' => $metric->metric_value,
                'unit' => $metric->metric_unit,
                'status' => $metric->status,
                'recommendation' => $metric->recommendation,
                'icon' => 'la-plug',
                'color' => $this->getMetricColor($metric->status),
                'measured_at' => $metric->measured_at
            ];
        }

        // Query cache metrics
        if (isset($recentMetrics['query_cache_hit_rate'])) {
            $metric = $recentMetrics['query_cache_hit_rate'];
            $metrics['query_cache'] = [
                'name' => __('admin.database.metrics.query_cache_hit_rate'),
                'value' => $metric->metric_value,
                'unit' => $metric->metric_unit,
                'status' => $metric->status,
                'recommendation' => $metric->recommendation,
                'icon' => 'la-tachometer-alt',
                'color' => $this->getMetricColor($metric->status),
                'measured_at' => $metric->measured_at
            ];
        }

        // Slow queries metrics
        if (isset($recentMetrics['slow_queries_per_minute'])) {
            $metric = $recentMetrics['slow_queries_per_minute'];
            $metrics['slow_queries'] = [
                'name' => __('admin.database.metrics.slow_queries_per_minute'),
                'value' => $metric->metric_value,
                'unit' => $metric->metric_unit,
                'status' => $metric->status,
                'recommendation' => $metric->recommendation,
                'icon' => 'la-hourglass-half',
                'color' => $this->getMetricColor($metric->status),
                'measured_at' => $metric->measured_at
            ];
        }

        // Memory usage metrics
        if (isset($recentMetrics['innodb_buffer_pool_usage'])) {
            $metric = $recentMetrics['innodb_buffer_pool_usage'];
            $metrics['memory_usage'] = [
                'name' => __('admin.database.metrics.innodb_buffer_pool_usage'),
                'value' => $metric->metric_value,
                'unit' => $metric->metric_unit,
                'status' => $metric->status,
                'recommendation' => $metric->recommendation,
                'icon' => 'la-memory',
                'color' => $this->getMetricColor($metric->status),
                'measured_at' => $metric->measured_at
            ];
        }

        // Disk usage metrics
        if (isset($recentMetrics['disk_usage'])) {
            $metric = $recentMetrics['disk_usage'];
            $metrics['disk_usage'] = [
                'name' => __('admin.database.metrics.disk_usage'),
                'value' => $metric->metric_value,
                'unit' => $metric->metric_unit,
                'status' => $metric->status,
                'recommendation' => $metric->recommendation,
                'icon' => 'la-hdd',
                'color' => $this->getMetricColor($metric->status),
                'measured_at' => $metric->measured_at
            ];
        }

        // Lock wait metrics
        if (isset($recentMetrics['lock_wait_time'])) {
            $metric = $recentMetrics['lock_wait_time'];
            $metrics['lock_waits'] = [
                'name' => __('admin.database.metrics.lock_wait_time'),
                'value' => $metric->metric_value,
                'unit' => $metric->metric_unit,
                'status' => $metric->status,
                'recommendation' => $metric->recommendation,
                'icon' => 'la-lock',
                'color' => $this->getMetricColor($metric->status),
                'measured_at' => $metric->measured_at
            ];
        }

        return $metrics;
    }

    /**
     * Get health trend over last 7 days
     */
    private function getHealthTrend()
    {
        try {
            $trend = [];
            
            // Get daily average health scores for last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();
                
                $dayMetrics = DatabaseHealthMetric::whereBetween('measured_at', [$dayStart, $dayEnd])
                    ->get()
                    ->groupBy('metric_name');
                
                $dayScore = $this->calculateHealthScore($dayMetrics);
                
                $trend[] = [
                    'date' => $date->format('M d'),
                    'score' => $dayScore,
                    'day' => $date->format('l')
                ];
            }
            
            return $trend;
        } catch (\Exception $e) {
            Log::error('Failed to get health trend: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get color class for metric status
     */
    private function getMetricColor($status)
    {
        return match($status) {
            'good' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Calculate overall health score (0-100) - enhanced version
     */
    private function calculateHealthScore($recentMetrics)
    {
        if ($recentMetrics->isEmpty()) {
            return 75; // Default score when no data
        }

        $scores = [];
        $weights = [];
        
        // Connection usage score (lower is better) - weight: 20%
        if (isset($recentMetrics['connection_usage'])) {
            $usage = $recentMetrics['connection_usage']->metric_value ?? $recentMetrics['connection_usage']->first()->metric_value;
            if ($usage < 50) $scores[] = 100;
            elseif ($usage < 75) $scores[] = 80;
            elseif ($usage < 90) $scores[] = 60;
            else $scores[] = 30;
            $weights[] = 20;
        }

        // Query cache hit rate score (higher is better) - weight: 25%
        if (isset($recentMetrics['query_cache_hit_rate'])) {
            $hitRate = $recentMetrics['query_cache_hit_rate']->metric_value ?? $recentMetrics['query_cache_hit_rate']->first()->metric_value;
            if ($hitRate > 95) $scores[] = 100;
            elseif ($hitRate > 90) $scores[] = 80;
            elseif ($hitRate > 80) $scores[] = 60;
            else $scores[] = 40;
            $weights[] = 25;
        }

        // Slow queries score (lower is better) - weight: 30%
        if (isset($recentMetrics['slow_queries_per_minute'])) {
            $slowQueries = $recentMetrics['slow_queries_per_minute']->metric_value ?? $recentMetrics['slow_queries_per_minute']->first()->metric_value;
            if ($slowQueries == 0) $scores[] = 100;
            elseif ($slowQueries < 1) $scores[] = 80;
            elseif ($slowQueries < 5) $scores[] = 60;
            else $scores[] = 30;
            $weights[] = 30;
        }

        // Memory usage score - weight: 15%
        if (isset($recentMetrics['innodb_buffer_pool_usage'])) {
            $memUsage = $recentMetrics['innodb_buffer_pool_usage']->metric_value ?? $recentMetrics['innodb_buffer_pool_usage']->first()->metric_value;
            if ($memUsage < 80) $scores[] = 100;
            elseif ($memUsage < 90) $scores[] = 80;
            elseif ($memUsage < 95) $scores[] = 60;
            else $scores[] = 40;
            $weights[] = 15;
        }

        // Lock wait time score - weight: 10%
        if (isset($recentMetrics['lock_wait_time'])) {
            $lockWait = $recentMetrics['lock_wait_time']->metric_value ?? $recentMetrics['lock_wait_time']->first()->metric_value;
            if ($lockWait < 100) $scores[] = 100;
            elseif ($lockWait < 500) $scores[] = 80;
            elseif ($lockWait < 1000) $scores[] = 60;
            else $scores[] = 30;
            $weights[] = 10;
        }

        // Calculate weighted average
        if (empty($scores)) {
            return 75;
        }

        $totalWeight = array_sum($weights);
        $weightedSum = 0;
        
        for ($i = 0; $i < count($scores); $i++) {
            $weightedSum += $scores[$i] * $weights[$i];
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight) : 75;
    }

    /**
     * Run database health check command via AJAX
     */
    public function runHealthCheck(Request $request)
    {
        $store = $request->boolean('store', true); // Default to store metrics
        $alert = $request->boolean('alert', true); // Default to create alerts

        try {
            $commandOptions = [
                '--store' => $store,
                '--alert' => $alert
            ];

            Artisan::call('db:health-check', $commandOptions);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => __('admin.database.health_check_completed'),
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Database health check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.health_check_failed'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Run database monitor command via AJAX
     */
    public function runMonitor(Request $request)
    {
        try {
            \Artisan::call('db:monitor', [
                '--no-interaction' => true
            ]);
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Database monitoring completed successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error("Database monitoring failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database monitoring failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run database optimize command via AJAX
     */
    public function runOptimize(Request $request)
    {
        $table = $request->get('table', 'all');
        $analyze = $request->boolean('analyze');
        $repair = $request->boolean('repair');

        try {
            $command = 'db:optimize';
            $parameters = [
                '--table' => $table,
                '--force' => true,
                '--no-interaction' => true
            ];

            if ($analyze) {
                $parameters['--analyze'] = true;
            }

            if ($repair) {
                $parameters['--repair'] = true;
            }

            \Artisan::call($command, $parameters);
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Database optimization completed successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error("Database optimization failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'table' => $table,
                'analyze' => $analyze,
                'repair' => $repair
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database optimization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run database archive command via AJAX
     */
    public function runArchive(Request $request)
    {
        $table = $request->get('table', 'invoices');
        $dryRun = $request->boolean('dry_run');

        try {
            $commandOptions = [
                '--table' => $table,
                '--dry-run' => $dryRun
            ];  

            Artisan::call('db:archive', $commandOptions);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => __('admin.database.archive_completed'),
                'output' => $output
            ]);
        } catch (\Exception $e) {
            Log::error('Database archive failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.archive_failed'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Run database commands via AJAX
     */
    public function runCommand(Request $request)
    {
        $command = $request->input('command');
        
        try {
            switch ($command) {
                case 'optimize':
                    return $this->runOptimize($request);
                case 'archive':
                    return $this->runArchive($request);
                case 'monitor':
                    return $this->runMonitor($request);
                case 'health-check':
                    return $this->runHealthCheck($request);
                case 'get-trends':
                    return $this->getPerformanceTrendsByType($request);
                case 'clean-metrics':
                    return $this->cleanPerformanceMetrics($request);
                case 'metrics-stats':
                    return $this->getMetricsStats($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => __('admin.database.unknown_command'),
                        'error' => 'Unknown command: ' . $command
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Database command failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.command_failed'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get recent maintenance tasks with enhanced details and space savings calculation
     */
    private function getRecentTasksWithDetails()
    {
        try {
            $tasks = DatabaseMaintenanceLog::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($task) {
                    // Calculate space savings from results JSON
                    $spaceSavings = $this->calculateSpaceSavings($task->results);
                    
                    // Calculate duration in seconds
                    $durationSeconds = 0;
                    if ($task->started_at && $task->completed_at) {
                        $durationSeconds = $task->completed_at->diffInSeconds($task->started_at);
                    }
                    
                    return [
                        'id' => $task->id,
                        'task_type' => $task->task_type,
                        'table_name' => $task->table_name,
                        'status' => $task->status,
                        'space_savings' => $spaceSavings,
                        'duration_seconds' => $durationSeconds,
                        'started_at' => $task->started_at,
                        'completed_at' => $task->completed_at,
                        'description' => $task->description,
                        'results' => $task->results
                    ];
                });

            return $tasks;
        } catch (\Exception $e) {
            Log::error('Failed to get recent tasks: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Clean performance metrics data
     */
    public function cleanPerformanceMetrics(Request $request)
    {
        $cleanupType = $request->input('cleanup_type', 'old'); // 'old', 'sample', 'all'
        $days = $request->input('days', 90); // Keep last N days
        
        try {
            $deletedCount = 0;
            $message = '';
            
            switch ($cleanupType) {
                case 'old':
                    // Delete metrics older than specified days
                    $deletedCount = PerformanceMetric::where('measured_at', '<', now()->subDays($days))->delete();
                    $message = __('admin.database.cleaned_old_metrics', ['count' => $deletedCount, 'days' => $days]);
                    break;
                    
                case 'sample':
                    // Delete only sample/generated data
                    $deletedCount = PerformanceMetric::whereJsonContains('metadata->generated', true)->delete();
                    $message = __('admin.database.cleaned_sample_metrics', ['count' => $deletedCount]);
                    break;
                    
                case 'duplicate':
                    // Remove duplicate metrics (keep latest per day per type)
                    $this->removeDuplicateMetrics();
                    $message = __('admin.database.cleaned_duplicate_metrics');
                    break;
                    
                case 'all':
                    // Delete all metrics (use with caution)
                    $deletedCount = PerformanceMetric::count();
                    PerformanceMetric::truncate();
                    $message = __('admin.database.cleaned_all_metrics', ['count' => $deletedCount]);
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => __('admin.database.invalid_cleanup_type')
                    ]);
            }
            
            Log::info("Performance metrics cleanup completed", [
                'type' => $cleanupType,
                'deleted_count' => $deletedCount,
                'days' => $days
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to clean performance metrics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.cleanup_failed') . ': ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Remove duplicate metrics keeping only the latest per day per type
     */
    private function removeDuplicateMetrics()
    {
        // Get duplicate metrics (more than one per day per type)
        $duplicates = DB::select("
            SELECT metric_type, DATE(measured_at) as date, COUNT(*) as count
            FROM performance_metrics 
            GROUP BY metric_type, DATE(measured_at)
            HAVING COUNT(*) > 1
        ");
        
        foreach ($duplicates as $duplicate) {
            // Keep only the latest metric for each day/type combination
            $keepIds = PerformanceMetric::where('metric_type', $duplicate->metric_type)
                ->whereDate('measured_at', $duplicate->date)
                ->orderBy('measured_at', 'desc')
                ->limit(1)
                ->pluck('id');
                
            // Delete the rest
            PerformanceMetric::where('metric_type', $duplicate->metric_type)
                ->whereDate('measured_at', $duplicate->date)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }
    
    /**
     * Get performance metrics statistics for cleanup info
     */
    public function getMetricsStats(Request $request)
    {
        try {
            $stats = [
                'total_metrics' => PerformanceMetric::count(),
                'sample_metrics' => PerformanceMetric::whereJsonContains('metadata->generated', true)->count(),
                'oldest_metric' => PerformanceMetric::min('measured_at'),
                'newest_metric' => PerformanceMetric::max('measured_at'),
                'metrics_by_type' => PerformanceMetric::select('metric_type')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('metric_type')
                    ->get(),
                'metrics_by_day' => PerformanceMetric::select(DB::raw('DATE(measured_at) as date'))
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy(DB::raw('DATE(measured_at)'))
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get()
            ];
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get metrics stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.stats_failed') . ': ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate space savings from task results JSON
     */
    private function calculateSpaceSavings($results)
    {
        if (!$results || !is_array($results)) {
            return ['amount' => 0, 'unit' => 'MB', 'formatted' => '0 MB'];
        }

        $totalSavings = 0; // in bytes
        
        // Check different result formats based on task type
        if (isset($results['space_freed'])) {
            // Direct space freed value (in bytes)
            $totalSavings = (float) $results['space_freed'];
        } elseif (isset($results['size_before']) && isset($results['size_after'])) {
            // Before/after comparison
            $totalSavings = (float) $results['size_before'] - (float) $results['size_after'];
        } elseif (isset($results['optimized_tables'])) {
            // Multiple table optimization results
            foreach ($results['optimized_tables'] as $table) {
                if (isset($table['space_freed'])) {
                    $totalSavings += (float) $table['space_freed'];
                }
            }
        } elseif (isset($results['archived_records']) && isset($results['estimated_space_freed'])) {
            // Archive task results
            $totalSavings = (float) $results['estimated_space_freed'];
        }

        // Convert bytes to appropriate unit
        if ($totalSavings >= 1024 * 1024 * 1024) {
            // GB
            $amount = round($totalSavings / (1024 * 1024 * 1024), 2);
            $unit = 'GB';
        } elseif ($totalSavings >= 1024 * 1024) {
            // MB
            $amount = round($totalSavings / (1024 * 1024), 2);
            $unit = 'MB';
        } elseif ($totalSavings >= 1024) {
            // KB
            $amount = round($totalSavings / 1024, 2);
            $unit = 'KB';
        } else {
            // Bytes
            $amount = round($totalSavings, 0);
            $unit = 'B';
        }

        return [
            'amount' => $amount,
            'unit' => $unit,
            'formatted' => $amount . ' ' . $unit,
            'bytes' => $totalSavings
        ];
    }

    /**
     * Resolve database health alert via AJAX
     */
    public function resolveAlert(Request $request)
    {
        $alertId = $request->input('alert_id');

        try {
            $alert = DatabaseHealthAlert::findOrFail($alertId);
            $alert->resolve();

            return response()->json([
                'success' => true,
                'message' => __('admin.database.alert_resolved_successfully')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resolve alert: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.alert_resolve_failed') . ': ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate sample performance metrics for testing
     */
    public function generateSampleMetrics(Request $request)
    {
        try {
            $metricsGenerated = 0;
            
            // Generate metrics for the last 30 days
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                
                // Generate 2-4 metrics per day with different times
                $metricsPerDay = rand(2, 4);
                
                for ($j = 0; $j < $metricsPerDay; $j++) {
                    $metricTime = $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59));
                    
                    // Generate realistic query time metrics (10-150ms)
                    $queryTime = rand(10, 150) + (rand(0, 100) / 100);
                    
                    PerformanceMetric::create([
                        'metric_type' => 'query_time',
                        'table_name' => null,
                        'query_type' => 'mixed',
                        'metric_value' => $queryTime,
                        'metric_unit' => 'ms',
                        'metadata' => [
                            'generated' => true,
                            'sample_data' => true
                        ],
                        'measured_at' => $metricTime,
                        'created_at' => $metricTime,
                        'updated_at' => $metricTime
                    ]);
                    
                    $metricsGenerated++;
                }
            }
            
            Log::info("Generated $metricsGenerated sample performance metrics");
            
            return response()->json([
                'success' => true,
                'message' => __('admin.database.sample_metrics_generated_count', ['count' => $metricsGenerated])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate sample metrics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('admin.database.sample_generation_failed') . ': ' . $e->getMessage()
            ]);
        }
    }
}
