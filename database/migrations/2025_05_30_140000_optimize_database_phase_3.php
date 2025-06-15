<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class OptimizeDatabasePhase3 extends Migration
{
    /**
     * Run the migrations.
     * Phase 3: Advanced optimization - Archiving, monitoring, and maintenance
     *
     * @return void
     */
    public function up()
    {
        echo "Phase 3: Implementing advanced database optimizations...\n";

        // 1. CREATE ARCHIVE TABLES FOR DATA RETENTION
        $this->createArchiveTables();

        // 2. CREATE MONITORING VIEWS FOR PERFORMANCE ANALYSIS
        $this->createMonitoringViews();

        // 3. CREATE DATABASE HEALTH CHECK SYSTEM
        $this->createHealthCheckSystem();

        // 4. CREATE MAINTENANCE HELPER TABLES
        $this->createMaintenanceTables();

        echo "Phase 3 optimization completed successfully!\n";
        echo "Created archive tables, monitoring views, and maintenance structures.\n";
    }

    /**
     * Create archive tables for old data retention
     */
    private function createArchiveTables()
    {
        // Archive table for old invoices (older than 3 years)
        Schema::create('invoices_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id'); // Reference to original invoice ID
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('invoice_vs', 50);
            $table->string('invoice_ks', 20)->nullable();
            $table->string('invoice_ss', 20)->nullable();
            $table->date('issue_date');
            $table->integer('due_in')->nullable();
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->string('payment_currency', 3)->default('EUR');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->unsignedBigInteger('payment_status_id')->nullable();
            $table->json('invoice_data'); // Store complete invoice data as JSON
            $table->timestamp('archived_at');
            $table->timestamps();

            // Essential indexes for archive queries
            $table->index(['user_id', 'archived_at'], 'idx_archive_user_archived');
            $table->index(['original_id'], 'idx_archive_original_id');
            $table->index(['issue_date'], 'idx_archive_issue_date');
        });

        // Archive table for old invoice products
        Schema::create('invoice_products_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_invoice_id');
            $table->unsignedBigInteger('archive_invoice_id'); // Reference to invoices_archive
            $table->json('products_data'); // Store all products as JSON
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->index(['archive_invoice_id'], 'idx_archive_products_invoice');
            $table->index(['original_invoice_id'], 'idx_archive_products_original');
        });
    }

    /**
     * Create monitoring views for performance analysis
     */
    private function createMonitoringViews()
    {
        // User activity summary view
        DB::statement("
            CREATE OR REPLACE VIEW user_activity_summary AS
            SELECT 
                u.id as user_id,
                u.name as user_name,
                u.email as user_email,
                COUNT(DISTINCT i.id) as total_invoices,
                COUNT(DISTINCT c.id) as total_clients,
                COUNT(DISTINCT s.id) as total_suppliers,
                COUNT(DISTINCT p.id) as total_products,
                MAX(i.created_at) as last_invoice_date,
                SUM(CASE WHEN i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as invoices_last_30_days,
                SUM(CASE WHEN i.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as invoices_last_7_days
            FROM users u
            LEFT JOIN invoices i ON u.id = i.user_id
            LEFT JOIN clients c ON u.id = c.user_id
            LEFT JOIN suppliers s ON u.id = s.user_id
            LEFT JOIN products p ON u.id = p.user_id
            GROUP BY u.id, u.name, u.email
        ");

        // Invoice statistics view
        DB::statement("
            CREATE OR REPLACE VIEW invoice_statistics AS
            SELECT 
                DATE_FORMAT(issue_date, '%Y-%m') as month_year,
                user_id,
                COUNT(*) as invoice_count,
                SUM(payment_amount) as total_revenue,
                AVG(payment_amount) as avg_invoice_amount,
                MIN(payment_amount) as min_invoice_amount,
                MAX(payment_amount) as max_invoice_amount,
                COUNT(CASE WHEN payment_status_id = 1 THEN 1 END) as paid_invoices,
                COUNT(CASE WHEN payment_status_id = 2 THEN 1 END) as unpaid_invoices,
                COUNT(CASE WHEN payment_status_id = 3 THEN 1 END) as overdue_invoices
            FROM invoices 
            WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(issue_date, '%Y-%m'), user_id
            ORDER BY month_year DESC, user_id
        ");

        // Database size monitoring view
        DB::statement("
            CREATE OR REPLACE VIEW database_size_monitor AS
            SELECT 
                TABLE_NAME as table_name,
                TABLE_ROWS as row_count,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb,
                ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_size_mb,
                ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_size_mb,
                ENGINE as storage_engine
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ");

        // Performance monitoring view for slow queries identification
        DB::statement("
            CREATE OR REPLACE VIEW query_performance_monitor AS
            SELECT 
                'invoices' as table_name,
                'user_invoices_listing' as query_type,
                COUNT(*) as row_count,
                'SELECT with user_id filter' as description
            FROM invoices
            UNION ALL
            SELECT 
                'clients' as table_name,
                'user_clients_listing' as query_type,
                COUNT(*) as row_count,
                'SELECT with user_id filter' as description
            FROM clients
            UNION ALL
            SELECT 
                'suppliers' as table_name,
                'user_suppliers_listing' as query_type,
                COUNT(*) as row_count,
                'SELECT with user_id filter' as description
            FROM suppliers
        ");
    }

    /**
     * Create database health monitoring system instead of static MySQL recommendations
     */
    private function createHealthCheckSystem()
    {
        // Create table for real-time database health metrics
        Schema::create('database_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name'); // 'connections_used', 'query_cache_hit_rate', 'slow_queries_count', etc.
            $table->decimal('metric_value', 15, 4);
            $table->string('metric_unit')->nullable(); // '%', 'MB', 'count', 'ms'
            $table->enum('status', ['good', 'warning', 'critical'])->default('good');
            $table->text('recommendation')->nullable(); // Dynamic recommendation based on actual values
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->index(['metric_name', 'measured_at'], 'idx_health_metric_time');
            $table->index(['status', 'measured_at'], 'idx_health_status_time');
        });

        // Create table for database health alerts
        Schema::create('database_health_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type'); // 'high_connections', 'low_cache_hit', 'slow_queries', etc.
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->text('message');
            $table->json('metric_data')->nullable(); // Store related metrics data
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['alert_type', 'severity'], 'idx_alert_type_severity');
            $table->index(['resolved', 'created_at'], 'idx_alert_resolved_time');
        });
    }

    /**
     * Create maintenance helper tables
     */
    private function createMaintenanceTables()
    {
        // Table for tracking database maintenance tasks
        Schema::create('database_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_type'); // 'optimize', 'analyze', 'repair', 'archive', 'cleanup'
            $table->string('table_name');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->text('description')->nullable();
            $table->json('results')->nullable(); // Store task results
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['task_type', 'status'], 'idx_maintenance_task_status');
            $table->index(['table_name', 'created_at'], 'idx_maintenance_table_date');
        });

        // Performance metrics tracking table
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // 'query_time', 'table_size', 'index_usage', etc.
            $table->string('table_name')->nullable();
            $table->string('query_type')->nullable();
            $table->decimal('metric_value', 10, 4);
            $table->string('metric_unit'); // 'seconds', 'MB', 'rows', etc.
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->index(['metric_type', 'measured_at'], 'idx_metrics_type_date');
            $table->index(['table_name', 'measured_at'], 'idx_metrics_table_date');
        });

        // Archive policy configuration
        Schema::create('archive_policies', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->integer('retention_months')->default(36); // 3 years default
            $table->string('date_column')->default('created_at');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_archived_at')->nullable();
            $table->integer('records_archived')->default(0);
            $table->timestamps();

            $table->unique('table_name');
        });

        // Insert default archive policies
        $archivePolicies = [
            ['table_name' => 'invoices', 'retention_months' => 36, 'date_column' => 'created_at'],
            ['table_name' => 'invoice_products', 'retention_months' => 36, 'date_column' => 'created_at'],
            ['table_name' => 'activity_log', 'retention_months' => 12, 'date_column' => 'created_at'], // If exists
        ];

        foreach ($archivePolicies as $policy) {
            DB::table('archive_policies')->insert([
                'table_name' => $policy['table_name'],
                'retention_months' => $policy['retention_months'],
                'date_column' => $policy['date_column'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        echo "Rolling back Phase 3 optimizations...\n";

        // Drop maintenance tables
        Schema::dropIfExists('archive_policies');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('database_maintenance_logs');
        Schema::dropIfExists('database_health_alerts');
        Schema::dropIfExists('database_health_metrics');

        // Drop monitoring views
        DB::statement("DROP VIEW IF EXISTS query_performance_monitor");
        DB::statement("DROP VIEW IF EXISTS database_size_monitor");
        DB::statement("DROP VIEW IF EXISTS invoice_statistics");
        DB::statement("DROP VIEW IF EXISTS user_activity_summary");

        // Drop archive tables
        Schema::dropIfExists('invoice_products_archive');
        Schema::dropIfExists('invoices_archive');

        echo "Phase 3 rollback completed.\n";
    }
}
