<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('invoice_vs', 50);
            $table->string('invoice_ks', 20)->nullable();
            $table->string('invoice_ss', 20)->nullable();
            $table->integer('due_in');
            $table->unsignedBigInteger('payment_status_id')->nullable();
            $table->date('issue_date');
            $table->date('tax_point_date')->nullable();
            $table->decimal(
                'payment_amount',
                18,
                2,
            );
            $table->string('payment_currency', 3);
            $table->string('template')->default('default');
            $table->string('invoice_logo')->nullable();
            $table->timestamps();
            $table->text('invoice_text')->nullable();

            $table->index(['user_id', 'created_at'], 'idx_invoices_user_created');
            $table->index(['user_id', 'issue_date'], 'idx_invoices_user_issue_date');
            $table->index(['user_id', 'client_id'], 'idx_invoices_user_client');
            $table->index(
                [
                    'user_id',
                    'issue_date',
                    'due_in',
                ],
                'idx_invoices_payment_calc',
            );
            $table->index(['user_id', 'payment_status_id'], 'idx_invoices_user_payment_status');
            $table->index(['user_id', 'invoice_vs'], 'idx_invoices_user_invoice_vs');
        });

        Schema::create('invoice_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal(
                'price',
                15,
                2,
            )->nullable();
            $table->string('currency', 3);
            $table->string('unit')->default('pieces');
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_custom_product')->default(false);
            $table->decimal(
                'tax_rate',
                5,
                2,
            )->nullable();
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['invoice_id', 'product_id']);
            $table->index(['invoice_id', 'created_at'], 'idx_invoice_products_invoice_created');
            $table->index(['product_id', 'created_at'], 'idx_invoice_products_product_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_products');
        Schema::dropIfExists('invoices');
    }
};
