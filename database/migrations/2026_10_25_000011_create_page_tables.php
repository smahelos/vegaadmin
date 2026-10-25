<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translatable columns are JSON objects keyed by locale (Backpack translatable fields).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->index()->constrained('pages')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->index()->constrained('page_categories')->nullOnDelete();
            $table->json('name')->nullable();
            $table->json('slug')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('main_image')->nullable();
            $table->json('images')->nullable();
            $table->boolean('published')->default(false)->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('publishing_start')->nullable();
            $table->dateTime('publishing_end')->nullable();
            $table->timestamps();

            $table->index([
                'published',
                'publishing_start',
                'publishing_end',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('page_categories');
    }
};
