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
        Schema::create('mysql_optimization_logs', function (Blueprint $table) {
            $table->id();
            $table->string('setting_name');
            $table->string('current_value')->nullable();
            $table->string('recommended_value');
            $table->text('description')->nullable();
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->boolean('applied')->default(false);
            $table->timestamps();

            $table->index(['priority', 'applied'], 'idx_optimization_priority_applied');
            $table->index(['setting_name', 'applied'], 'idx_optimization_setting_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mysql_optimization_logs');
    }
};
