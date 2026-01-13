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
        Schema::create('entity_limits', function (Blueprint $table) {
            $table->id();
            $table->string('permission_name', 255)->comment('Permission that this limit applies to');
            $table->string('entity_type', 255)->comment('Entity type: invoice, client, supplier, product, etc.');
            $table->integer('limit_value')->comment('Maximum allowed value for this limit');
            $table->string('default_period', 20)->default('monthly')->comment('Default period type for this entity');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->string('metric_type', 255)->default('count')->comment('Type of metric: count, value, size');
            $table->string('description', 255)->nullable()->comment('Human readable description');
            $table->tinyInteger('is_active')->default(1)->comment('Whether this limit is currently active');
            $table->timestamps();

            // Indexes
            $table->index(['permission_name'], 'entity_limits_permission_name_index');
            $table->index(['permission_name', 'entity_type'], 'entity_limits_permission_name_entity_type_index');
            $table->index(['entity_type', 'period_type'], 'entity_limits_entity_type_period_type_index');
            $table->unique(['permission_name', 'entity_type', 'period_type', 'metric_type'], 'entity_limits_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_limits');
    }
};
