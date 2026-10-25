<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_limits', function (Blueprint $table): void {
            $table->id();
            $table->string('permission_name')->index()->comment('Permission that this limit applies to');
            $table->string('entity_type')->comment('Entity type: invoice, client, supplier, product, etc.');
            $table->integer('limit_value')->comment('Maximum allowed value for this limit');
            $table->string('default_period', 20)->default('monthly')->comment('Default period type for this entity');
            $table->enum('period_type', [
                'daily',
                'weekly',
                'monthly',
                'yearly',
                'lifetime',
            ])->default('monthly');
            $table->string('metric_type')->default('count')->comment('Type of metric: count, value, size');
            $table->string('description')->nullable()->comment('Human readable description');
            $table->tinyInteger('is_active')->default(1)->comment('Whether this limit is currently active');
            $table->timestamps();

            $table->unique([
                'permission_name',
                'entity_type',
                'period_type',
                'metric_type',
            ], 'entity_limits_unique');
            $table->index(['permission_name', 'entity_type']);
            $table->index(['entity_type', 'period_type']);
        });

        Schema::create('entity_limit_usage', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50)->comment('Type of entity (invoice, client, etc.)');
            $table->string('metric_type', 50)->comment('Metric type (count, value, size)');
            $table->string('period_type', 50)->comment('Period type (daily, monthly, etc.)');
            $table->dateTime('period_start')->comment('Start of the tracking period');
            $table->dateTime('period_end')->comment('End of the tracking period');
            $table->decimal('current_value', 15, 2)->default(0)->comment('Current usage value');
            $table->timestamp('last_reset_at')->nullable()->comment('When usage was last reset');
            $table->timestamps();

            $table->unique(
                [
                    'user_id',
                    'entity_type',
                    'metric_type',
                    'period_type',
                    'period_start',
                ],
                'unique_usage_record',
            );
            $table->index([
                'user_id',
                'entity_type',
                'metric_type',
            ]);
            $table->index(['period_start', 'period_end']);
            $table->index(['entity_type', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_limit_usage');
        Schema::dropIfExists('entity_limits');
    }
};
