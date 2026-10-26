<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->nullable()->comment('Color code for visual representation');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->restrictOnDelete();
            $table->date('expense_date');
            $table->decimal(
                'amount',
                15,
                2,
            );
            $table->string('currency', 3);
            $table->foreignId('payment_method_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->text('attachments');
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->boolean('tax_included')->default(true);
            $table->foreignId('status_id')->nullable()->constrained('statuses')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
