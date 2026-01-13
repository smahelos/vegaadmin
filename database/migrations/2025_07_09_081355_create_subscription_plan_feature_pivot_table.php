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
        // Drop table if it exists from previous failed migration
        Schema::dropIfExists('subscription_plan_subscription_plan_feature');
        
        Schema::create('subscription_plan_subscription_plan_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->onDelete('cascade')
                ->name('sp_spf_plan_fk'); // Custom short name
            $table->foreignId('subscription_plan_feature_id')
                ->constrained('subscription_plan_features')
                ->onDelete('cascade')
                ->name('sp_spf_feature_fk'); // Custom short name
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['subscription_plan_id', 'subscription_plan_feature_id'], 'sp_spf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_subscription_plan_feature');
    }
};
