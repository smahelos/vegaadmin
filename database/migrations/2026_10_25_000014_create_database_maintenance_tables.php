<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archive, monitoring and optimisation tables of the admin database dashboard and the db:* commands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_policies', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->unique();
            $table->integer('retention_months')->default(36);
            $table->string('date_column')->default('created_at');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_archived_at')->nullable();
            $table->integer('records_archived')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id');
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
            $table->json('invoice_data');
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->index(['user_id', 'archived_at'], 'idx_archive_user_archived');
            $table->index('original_id', 'idx_archive_original_id');
            $table->index('issue_date', 'idx_archive_issue_date');
        });

        Schema::create('invoice_products_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_invoice_id');
            $table->unsignedBigInteger('archive_invoice_id');
            $table->json('products_data');
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->index('archive_invoice_id', 'idx_archive_products_invoice');
            $table->index('original_invoice_id', 'idx_archive_products_original');
        });

        Schema::create('database_health_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type');
            $table->enum('severity', [
                'info',
                'warning',
                'critical',
            ]);
            $table->text('message');
            $table->json('metric_data')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['alert_type', 'severity'], 'idx_alert_type_severity');
            $table->index(['resolved', 'created_at'], 'idx_alert_resolved_time');
        });

        Schema::create('database_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->decimal(
                'metric_value',
                15,
                4,
            );
            $table->string('metric_unit')->nullable();
            $table->enum('status', [
                'good',
                'warning',
                'critical',
            ])->default('good');
            $table->text('recommendation')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->index(['metric_name', 'measured_at'], 'idx_health_metric_time');
            $table->index(['status', 'measured_at'], 'idx_health_status_time');
        });

        Schema::create('database_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_type');
            $table->string('table_name');
            $table->enum('status', [
                'pending',
                'running',
                'completed',
                'failed',
            ])->default('pending');
            $table->text('description')->nullable();
            $table->json('results')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['task_type', 'status'], 'idx_maintenance_task_status');
            $table->index(['table_name', 'created_at'], 'idx_maintenance_table_date');
        });

        Schema::create('mysql_optimization_logs', function (Blueprint $table) {
            $table->id();
            $table->string('setting_name');
            $table->string('current_value')->nullable();
            $table->string('recommended_value');
            $table->text('description')->nullable();
            $table->enum('priority', [
                'high',
                'medium',
                'low',
            ])->default('medium');
            $table->boolean('applied')->default(false);
            $table->timestamps();

            $table->index(['priority', 'applied'], 'idx_optimization_priority_applied');
            $table->index(['setting_name', 'applied'], 'idx_optimization_setting_applied');
        });

        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type');
            $table->string('table_name')->nullable();
            $table->string('query_type')->nullable();
            $table->decimal(
                'metric_value',
                10,
                4,
            );
            $table->string('metric_unit');
            $table->json('metadata')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->index(['metric_type', 'measured_at'], 'idx_metrics_type_date');
            $table->index(['table_name', 'measured_at'], 'idx_metrics_table_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('mysql_optimization_logs');
        Schema::dropIfExists('database_maintenance_logs');
        Schema::dropIfExists('database_health_metrics');
        Schema::dropIfExists('database_health_alerts');
        Schema::dropIfExists('invoice_products_archive');
        Schema::dropIfExists('invoices_archive');
        Schema::dropIfExists('archive_policies');
    }
};
