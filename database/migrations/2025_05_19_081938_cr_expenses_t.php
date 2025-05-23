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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('CZK');
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_file')->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->foreignId('status_id')->nullable()->constrained('statuses')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
