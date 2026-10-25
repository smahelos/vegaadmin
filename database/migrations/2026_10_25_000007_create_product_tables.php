<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('Supplier ID');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal(
                'price',
                10,
                2,
            );
            $table->string('unit')->default('pieces');
            $table->string('currency', 3);
            $table->foreignId('tax_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug'], 'products_user_slug_unique');
            $table->index(['user_id', 'created_at'], 'idx_products_user_created');
            $table->index(['user_id', 'name'], 'idx_products_user_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
