<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize {--table=all} {--analyze} {--repair} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database tables and analyze performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tableName = $this->option('table');
        $analyze = $this->option('analyze');
        $repair = $this->option('repair');
        $force = $this->option('force');

        $this->info('Database Optimization Tool');
        $this->info('=========================');

        if ($tableName === 'all') {
            $tables = $this->getAllTables();
        } else {
            $tables = [$tableName];
        }

        // If force is not set and we're in interactive mode, ask for confirmation
        if (! $force && $this->input->isInteractive() && ! $this->confirm('Do you want to proceed with optimization of ' . count($tables) . ' table(s)?')) {
            $this->info('Optimization cancelled.');

            return Command::SUCCESS;
        }

        foreach ($tables as $table) {
            $this->optimizeTable(
                $table,
                $analyze,
                $repair,
            );
        }

        $this->info("\n✅ Database optimization completed!");

        return Command::SUCCESS;
    }

    /**
     * Get all tables in the database
     */
    private function getAllTables(): array
    {
        return $this->getMySQLTables();
    }

    /**
     * Get all tables from MySQL
     */
    private function getMySQLTables(): array
    {
        $tables = DB::select("
            SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ");

        return array_map(function ($table) {
            return $table->TABLE_NAME;
        }, $tables);
    }

    /**
     * Optimize a specific table
     */
    private function optimizeTable(
        string $tableName,
        bool $analyze = false,
        bool $repair = false,
    ): void {
        $this->info("\n🔧 Optimizing table: {$tableName}");

        // Log maintenance task
        $maintenanceId = DB::table('database_maintenance_logs')->insertGetId([
            'task_type' => 'optimize',
            'table_name' => $tableName,
            'status' => 'running',
            'description' => "Optimizing table {$tableName}",
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $results = [];

            // Check table status before optimization
            $beforeStats = $this->getTableStats($tableName);
            $results['before'] = $beforeStats;

            $this->optimizeMySQLTable(
                $tableName,
                $analyze,
                $repair,
                $results,
            );

            // Check table status after optimization
            $afterStats = $this->getTableStats($tableName);
            $results['after'] = $afterStats;

            // Calculate improvement
            $sizeBefore = $beforeStats['size_mb'] ?? 0;
            $sizeAfter = $afterStats['size_mb'] ?? 0;
            $improvement = $sizeBefore > 0 ? (($sizeBefore - $sizeAfter) / $sizeBefore) * 100 : 0;

            if ($improvement > 0) {
                $this->line('  📉 Size reduced by: ' . round($improvement, 2) . "% ({$sizeBefore}MB → {$sizeAfter}MB)");
            } else {
                $this->line("  📊 Size: {$sizeAfter}MB (no reduction)");
            }

            // Update maintenance log with success
            DB::table('database_maintenance_logs')
                ->where('id', $maintenanceId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'results' => json_encode($results),
                    'updated_at' => now(),
                ]);

            $this->line('  ✅ Optimization completed successfully');

            // Store performance metric
            DB::table('performance_metrics')->insert([
                'metric_type' => 'optimization_improvement',
                'table_name' => $tableName,
                'metric_value' => $improvement,
                'metric_unit' => 'percent',
                'metadata' => json_encode([
                    'size_before_mb' => $sizeBefore,
                    'size_after_mb' => $sizeAfter,
                ]),
                'measured_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            // Update maintenance log with error
            DB::table('database_maintenance_logs')
                ->where('id', $maintenanceId)
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'results' => json_encode(['error' => $e->getMessage()]),
                    'updated_at' => now(),
                ]);

            $this->error('  ❌ Optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize MySQL table
     */
    private function optimizeMySQLTable(
        string $tableName,
        bool $analyze,
        bool $repair,
        array &$results,
    ): void {
        // Repair table if requested
        if ($repair) {
            $this->line('  🔨 Repairing table...');
            $repairResult = DB::select("REPAIR TABLE `{$tableName}`");
            $results['repair'] = $repairResult;
            $this->line('  ✅ Repair completed');
        }

        // Analyze table if requested
        if ($analyze) {
            $this->line('  📊 Analyzing table...');
            $analyzeResult = DB::select("ANALYZE TABLE `{$tableName}`");
            $results['analyze'] = $analyzeResult;
            $this->line('  ✅ Analysis completed');
        }

        // Optimize table
        $this->line('  ⚡ Optimizing table...');
        $optimizeResult = DB::select("OPTIMIZE TABLE `{$tableName}`");
        $results['optimize'] = $optimizeResult;
    }

    /**
     * Get table statistics
     */
    private function getTableStats(string $tableName): array
    {
        return $this->getMySQLTableStats($tableName);
    }

    /**
     * Get MySQL table statistics
     */
    private function getMySQLTableStats(string $tableName): array
    {
        $stats = DB::selectOne('
            SELECT
                TABLE_ROWS as row_count,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb,
                ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_size_mb,
                ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_size_mb,
                ENGINE as storage_engine,
                DATA_FREE as data_free
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
        ', [$tableName]);

        return [
            'row_count' => $stats->row_count ?? 0,
            'size_mb' => $stats->size_mb ?? 0,
            'data_size_mb' => $stats->data_size_mb ?? 0,
            'index_size_mb' => $stats->index_size_mb ?? 0,
            'storage_engine' => $stats->storage_engine ?? 'unknown',
            'data_free' => $stats->data_free ?? 0,
        ];
    }
}
