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
        Schema::create('artisan_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('parameters_description')->nullable();
            $table->foreignId('category_id')
                ->constrained('artisan_command_categories')
                ->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artisan_commands');
    }
};
