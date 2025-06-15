<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\DatabaseHealthMetric;
use App\Models\DatabaseHealthAlert;

class DatabaseHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health-check 
                            {--alert : Send alerts for critical issues}
                            {--store : Store metrics in database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database health metrics and generate alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Starting database health check...');

        $metrics = $this->collectHealthMetrics();
        $store = $this->option('store');
        $alert = $this->option('alert');

        foreach ($metrics as $metric) {
            // Display metric
            $status = $this->getStatusIcon($metric['status']);
            $this->line("  {$status} {$metric['name']}: {$metric['value']} {$metric['unit']} [{$metric['status']}]");
            
            if ($metric['recommendation']) {
                $this->line("    💡 {$metric['recommendation']}");
            }

            // Store in database if requested
            if ($store) {
                DatabaseHealthMetric::create([
                    'metric_name' => $metric['name'],
                    'metric_value' => $metric['value'],
                    'metric_unit' => $metric['unit'],
                    'status' => $metric['status'],
                    'recommendation' => $metric['recommendation'],
                    'measured_at' => now()
                ]);
            }

            // Create alert if critical and alerts enabled
            if ($alert && in_array($metric['status'], ['warning', 'critical'])) {
                $this->createAlert($metric);
            }
        }

        $this->info('✅ Database health check completed!');
        return Command::SUCCESS;
    }

    /**
     * Collect various health metrics
     */
    private function collectHealthMetrics()
    {
        $metrics = [];

        try {
            // Connection usage
            $connectionStats = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'");
            
            if ($connectionStats && $maxConnections) {
                $usage = round(($connectionStats->Value / $maxConnections->Value) * 100, 2);
                $metrics[] = [
                    'name' => 'connection_usage',
                    'value' => $usage,
                    'unit' => '%',
                    'status' => $usage > 80 ? 'critical' : ($usage > 60 ? 'warning' : 'good'),
                    'recommendation' => $usage > 80 ? 'Consider increasing max_connections or optimizing connection pooling' : null
                ];
            }

            // Query cache hit rate (if enabled)
            $cacheHits = DB::selectOne("SHOW STATUS LIKE 'Qcache_hits'");
            $cacheInserts = DB::selectOne("SHOW STATUS LIKE 'Qcache_inserts'");
            
            if ($cacheHits && $cacheInserts && ($cacheHits->Value + $cacheInserts->Value) > 0) {
                $hitRate = round(($cacheHits->Value / ($cacheHits->Value + $cacheInserts->Value)) * 100, 2);
                $metrics[] = [
                    'name' => 'query_cache_hit_rate',
                    'value' => $hitRate,
                    'unit' => '%',
                    'status' => $hitRate < 60 ? 'warning' : 'good',
                    'recommendation' => $hitRate < 60 ? 'Consider optimizing queries or increasing query cache size' : null
                ];
            }

            // Slow queries per minute
            $slowQueries = DB::selectOne("SHOW STATUS LIKE 'Slow_queries'");
            $uptime = DB::selectOne("SHOW STATUS LIKE 'Uptime'");
            
            if ($slowQueries && $uptime && $uptime->Value > 0) {
                $slowPerMinute = round(($slowQueries->Value / $uptime->Value) * 60, 2);
                $metrics[] = [
                    'name' => 'slow_queries_per_minute',
                    'value' => $slowPerMinute,
                    'unit' => 'queries/min',
                    'status' => $slowPerMinute > 5 ? 'critical' : ($slowPerMinute > 1 ? 'warning' : 'good'),
                    'recommendation' => $slowPerMinute > 1 ? 'Review and optimize slow queries' : null
                ];
            }

            // InnoDB buffer pool usage
            $bufferPoolSize = DB::selectOne("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_total'");
            $bufferPoolFree = DB::selectOne("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_free'");
            
            if ($bufferPoolSize && $bufferPoolFree && $bufferPoolSize->Value > 0) {
                $usage = round((1 - ($bufferPoolFree->Value / $bufferPoolSize->Value)) * 100, 2);
                $metrics[] = [
                    'name' => 'innodb_buffer_pool_usage',
                    'value' => $usage,
                    'unit' => '%',
                    'status' => $usage > 95 ? 'warning' : 'good',
                    'recommendation' => $usage > 95 ? 'Consider increasing innodb_buffer_pool_size' : null
                ];
            }

            // Table locks waiting
            $tableLocksWaited = DB::selectOne("SHOW STATUS LIKE 'Table_locks_waited'");
            $tableLocksImmediate = DB::selectOne("SHOW STATUS LIKE 'Table_locks_immediate'");
            
            if ($tableLocksWaited && $tableLocksImmediate && ($tableLocksWaited->Value + $tableLocksImmediate->Value) > 0) {
                $waitRate = round(($tableLocksWaited->Value / ($tableLocksWaited->Value + $tableLocksImmediate->Value)) * 100, 2);
                $metrics[] = [
                    'name' => 'table_locks_wait_rate',
                    'value' => $waitRate,
                    'unit' => '%',
                    'status' => $waitRate > 10 ? 'critical' : ($waitRate > 5 ? 'warning' : 'good'),
                    'recommendation' => $waitRate > 5 ? 'Consider optimizing table structure or using InnoDB instead of MyISAM' : null
                ];
            }

        } catch (\Exception $e) {
            $this->error("Error collecting metrics: " . $e->getMessage());
        }

        return $metrics;
    }

    /**
     * Create alert for critical metrics
     */
    private function createAlert($metric)
    {
        $severity = $metric['status'] === 'critical' ? 'critical' : 'warning';
        
        DatabaseHealthAlert::create([
            'alert_type' => $metric['name'],
            'severity' => $severity,
            'message' => "Database health metric '{$metric['name']}' is {$metric['status']}: {$metric['value']} {$metric['unit']}",
            'metric_data' => [
                'metric_name' => $metric['name'],
                'metric_value' => $metric['value'],
                'metric_unit' => $metric['unit'],
                'status' => $metric['status'],
                'recommendation' => $metric['recommendation']
            ],
            'resolved' => false
        ]);
    }

    /**
     * Get status icon for display
     */
    private function getStatusIcon($status)
    {
        return match($status) {
            'good' => '✅',
            'warning' => '⚠️',
            'critical' => '🚨',
            default => 'ℹ️'
        };
    }
}
