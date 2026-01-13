<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entity_limit_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('User ID, null for anonymous users');
            $table->string('entity_type', 50)->comment('Type of entity (invoice, client, etc.)');
            $table->string('metric_type', 50)->comment('Metric type (count, value, size)');
            $table->string('period_type', 50)->comment('Period type (daily, monthly, etc.)');
            $table->datetime('period_start')->comment('Start of the tracking period');
            $table->datetime('period_end')->comment('End of the tracking period');
            $table->decimal('current_value', 15, 2)->default(0)->comment('Current usage value');
            $table->timestamp('last_reset_at')->nullable()->comment('When usage was last reset');
            $table->timestamps();
            
            // Ensure one usage record per user per entity/metric/period combination
            $table->unique(['user_id', 'entity_type', 'metric_type', 'period_type', 'period_start'], 'unique_usage_record');
            
            // Indexes for performance
            $table->index(['user_id', 'entity_type', 'metric_type']);
            $table->index(['period_start', 'period_end']);
            $table->index(['entity_type', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_limit_usage');
    }
};
