<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artisan_command_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('artisan_commands', function (Blueprint $table): void {
            $table->id();
            $table->string('command')->unique();
            $table->text('signature')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('parameters_description')->nullable();
            $table->foreignId('category_id')->constrained('artisan_command_categories')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cron_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('command');
            $table->string('frequency');
            $table->string('custom_expression')->nullable();
            $table->time('run_at')->nullable();
            $table->integer('day_of_week')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('last_run')->nullable();
            $table->text('last_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_tasks');
        Schema::dropIfExists('artisan_commands');
        Schema::dropIfExists('artisan_command_categories');
    }
};
