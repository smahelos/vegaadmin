<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The commands a cron task may run are the ScheduledCommand enum, so the editable list is gone.
     */
    public function up(): void
    {
        Schema::dropIfExists('artisan_commands');
        Schema::dropIfExists('artisan_command_categories');
    }

    public function down(): void
    {
        Schema::create(
            'artisan_command_categories',
            function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            },
        );

        Schema::create(
            'artisan_commands',
            function (Blueprint $table): void {
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
            },
        );
    }
};
