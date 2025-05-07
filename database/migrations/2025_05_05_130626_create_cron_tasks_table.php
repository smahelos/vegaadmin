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
        Schema::create('cron_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('command');
            $table->string('frequency'); // daily, weekly, monthly, custom
            $table->string('custom_expression')->nullable(); // Pro vlastní cron expressions
            $table->time('run_at')->nullable(); // Čas spuštění pro denní/týdenní/měsíční úlohy
            $table->integer('day_of_week')->nullable(); // 0-6 pro týdenní úlohy
            $table->integer('day_of_month')->nullable(); // 1-31 pro měsíční úlohy
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('last_run')->nullable();
            $table->text('last_output')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_tasks');
    }
};
