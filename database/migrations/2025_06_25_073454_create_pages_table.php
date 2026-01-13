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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained('page_categories')->onDelete('set null');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->json('content')->nullable(); // Change content column to JSON type
            $table->string('main_image')->nullable();
            $table->json('images')->nullable();
            $table->boolean('published')->default(false);
            $table->foreignId('published_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('publishing_start')->nullable();
            $table->dateTime('publishing_end')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('published');
            $table->index('category_id');
            $table->index('parent_id');
            $table->index('sort_order');
            $table->index('slug');
            $table->index(['published', 'publishing_start', 'publishing_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
