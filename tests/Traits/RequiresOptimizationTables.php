<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Schema;

trait RequiresOptimizationTables
{
    /**
     * List of optimization tables that are created only in production
     */
    protected array $optimizationTables = [
        'performance_metrics',
        'database_health_metrics', 
        'user_activity_summary',
        'mysql_optimization_logs',
        'archive_policies',
        'database_maintenance_logs',
        'database_health_alerts'
    ];

    /**
     * Check if all required optimization tables exist
     */
    protected function hasOptimizationTables(): bool
    {
        foreach ($this->optimizationTables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Skip test if optimization tables don't exist
     * 
     * @param string|array $requiredTables Optional specific tables to check
     */
    protected function skipIfOptimizationTablesNotExist($requiredTables = null): void
    {
        $tablesToCheck = $requiredTables ? (array) $requiredTables : $this->optimizationTables;
        
        foreach ($tablesToCheck as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped(
                    "Test skipped: Optimization table '{$table}' not available in testing environment. " .
                    "This test requires MySQL optimization tables which are only created in production environment."
                );
            }
        }
    }

    /**
     * Skip test if specific table doesn't exist
     */
    protected function skipIfTableNotExists(string $table): void
    {
        if (!Schema::hasTable($table)) {
            $this->markTestSkipped(
                "Test skipped: Table '{$table}' not available in testing environment."
            );
        }
    }

    /**
     * Check if we're in a testing environment that skips optimization tables
     */
    protected function isOptimizationTablesSkipped(): bool
    {
        return app()->environment('testing') && !$this->hasOptimizationTables();
    }
}
