<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:monitor {--metric=all} {--store}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database performance and collect metrics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $metric = $this->option('metric');
        $store = $this->option('store');

        $this->info("Database Performance Monitor");
        $this->info("==========================");

        if ($metric === 'all' || $metric === 'size') {
            $this->checkDatabaseSize($store);
        }

        if ($metric === 'all' || $metric === 'indexes') {
            $this->checkIndexUsage($store);
        }

        if ($metric === 'all' || $metric === 'queries') {
            $this->checkSlowQueries($store);
        }

        if ($metric === 'all' || $metric === 'connections') {
            $this->checkConnections($store);
        }

        if ($metric === 'all' || $metric === 'activity') {
            $this->checkUserActivity();
        }

        return Command::SUCCESS;
    }

    /**
     * Check database and table sizes
     */
    private function checkDatabaseSize($store = false)
    {
        $this->info("\n📊 Database Size Analysis:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $sizes = DB::select("
            SELECT 
                TABLE_NAME as table_name,
                TABLE_ROWS as row_count,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb,
                ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_size_mb,
                ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_size_mb
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
            LIMIT 10
        ");

        $headers = ['Table', 'Rows', 'Total (MB)', 'Data (MB)', 'Index (MB)'];
        $data = [];

        foreach ($sizes as $size) {
            $data[] = [
                $size->table_name,
                number_format($size->row_count),
                $size->size_mb,
                $size->data_size_mb,
                $size->index_size_mb
            ];

            if ($store) {
                $this->storeMetric('table_size', $size->table_name, $size->size_mb, 'MB');
                $this->storeMetric('table_rows', $size->table_name, $size->row_count, 'rows');
            }
        }

        $this->table($headers, $data);

        // Total database size
        $totalSize = DB::selectOne("
            SELECT 
                ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as total_mb
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE()
        ");

        $this->info("💾 Total Database Size: {$totalSize->total_mb} MB");
    }

    /**
     * Check index usage and efficiency
     */
    private function checkIndexUsage($store = false)
    {
        $this->info("\n🔍 Index Usage Analysis:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Get index statistics
        $indexes = DB::select("
            SELECT 
                TABLE_NAME as table_name,
                INDEX_NAME as index_name,
                NON_UNIQUE as non_unique,
                CARDINALITY as cardinality,
                COLUMN_NAME as column_name
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND INDEX_NAME != 'PRIMARY'
            ORDER BY TABLE_NAME, INDEX_NAME
        ");

        $indexData = [];
        $currentIndex = null;
        
        foreach ($indexes as $index) {
            if ($currentIndex !== $index->index_name) {
                if ($currentIndex !== null) {
                    // Show previous index info
                }
                $currentIndex = $index->index_name;
            }
            
            $indexData[] = [
                $index->table_name,
                $index->index_name,
                $index->non_unique ? 'No' : 'Yes',
                number_format($index->cardinality),
                $index->column_name
            ];
        }

        $headers = ['Table', 'Index', 'Unique', 'Cardinality', 'Column'];
        $this->table($headers, array_slice($indexData, 0, 15)); // Show first 15

        if ($store) {
            $this->storeMetric('index_count', null, count($indexData), 'indexes');
        }
    }

    /**
     * Check for slow queries and performance issues
     */
    private function checkSlowQueries($store = false)
    {
        $this->info("\n⚡ Query Performance Analysis:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Check if slow query log is enabled
        $slowLogStatus = DB::selectOne("SHOW VARIABLES LIKE 'slow_query_log'");
        $longQueryTime = DB::selectOne("SHOW VARIABLES LIKE 'long_query_time'");

        $this->info("Slow Query Log: " . ($slowLogStatus->Value === 'ON' ? '✅ Enabled' : '❌ Disabled'));
        $this->info("Long Query Time: {$longQueryTime->Value}s");

        // Test query performance on main tables
        $this->info("\n📈 Testing Query Performance:");
        
        $testQueries = [
            'invoices_user_filter' => "SELECT COUNT(*) FROM invoices WHERE user_id = 1",
            'clients_user_filter' => "SELECT COUNT(*) FROM clients WHERE user_id = 1",
            'suppliers_user_filter' => "SELECT COUNT(*) FROM suppliers WHERE user_id = 1",
            'invoices_date_range' => "SELECT COUNT(*) FROM invoices WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        ];

        foreach ($testQueries as $queryName => $query) {
            $startTime = microtime(true);
            DB::select($query);
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $status = $executionTime < 10 ? '✅' : ($executionTime < 50 ? '⚠️' : '❌');
            $this->line("{$status} {$queryName}: {$executionTime}ms");

            if ($store) {
                $this->storeMetric('query_time', null, $executionTime, 'ms', [
                    'query_name' => $queryName,
                    'query' => $query
                ]);
            }
        }
    }

    /**
     * Check database connections
     */
    private function checkConnections($store = false)
    {
        $this->info("\n🔗 Connection Analysis:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━");

        $processlist = DB::select("SHOW PROCESSLIST");
        $connections = count($processlist);

        $maxConnections = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'");
        $threadsConnected = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");

        $this->info("Current Connections: {$threadsConnected->Value}");
        $this->info("Max Connections: {$maxConnections->Value}");
        $this->info("Connection Usage: " . round(($threadsConnected->Value / $maxConnections->Value) * 100, 1) . "%");

        if ($store) {
            $this->storeMetric('connections_current', null, $threadsConnected->Value, 'connections');
            $this->storeMetric('connections_max', null, $maxConnections->Value, 'connections');
        }
    }

    /**
     * Check user activity patterns
     */
    private function checkUserActivity()
    {
        $this->info("\n👥 User Activity Summary:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $userStats = DB::select("
            SELECT 
                user_id,
                user_name,
                user_email,
                total_invoices,
                total_clients,
                total_suppliers,
                invoices_last_30_days,
                invoices_last_7_days
            FROM user_activity_summary 
            ORDER BY total_invoices DESC 
            LIMIT 10
        ");

        $headers = ['User ID', 'Name', 'Total Invoices', 'Last 30d', 'Last 7d', 'Clients', 'Suppliers'];
        $data = [];

        foreach ($userStats as $stat) {
            $data[] = [
                $stat->user_id,
                substr($stat->user_name, 0, 20),
                $stat->total_invoices,
                $stat->invoices_last_30_days,
                $stat->invoices_last_7_days,
                $stat->total_clients,
                $stat->total_suppliers
            ];
        }

        $this->table($headers, $data);
    }

    /**
     * Store metric in performance_metrics table
     */
    private function storeMetric($metricType, $tableName, $value, $unit, $metadata = [])
    {
        DB::table('performance_metrics')->insert([
            'metric_type' => $metricType,
            'table_name' => $tableName,
            'metric_value' => $value,
            'metric_unit' => $unit,
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'measured_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
