<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issued documents stay in the main tables for 10 years (D-050), so the archive policies and the invoice archive
 * that db:archive filled are gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('invoice_products_archive');
        Schema::dropIfExists('invoices_archive');
        Schema::dropIfExists('archive_policies');
    }

    public function down(): void
    {
        Schema::create(
            'archive_policies',
            function (Blueprint $table): void {
                $table->id();
                $table->string('table_name')->unique();
                $table->integer('retention_months')->default(36);
                $table->string('date_column')->default('created_at');
                $table->boolean('enabled')->default(true);
                $table->timestamp('last_archived_at')->nullable();
                $table->integer('records_archived')->default(0);
                $table->timestamps();
            },
        );

        Schema::create(
            'invoices_archive',
            function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('original_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('business_id')->nullable();
                $table->unsignedBigInteger('contact_id')->nullable();
                $table->string('invoice_vs', 50);
                $table->string('invoice_ks', 20)->nullable();
                $table->string('invoice_ss', 20)->nullable();
                $table->date('issue_date');
                $table->integer('due_in')->nullable();
                $table->decimal(
                    'payment_amount',
                    10,
                    2,
                )->default(0);
                $table->string('payment_currency', 3)->default('EUR');
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->json('invoice_data');
                $table->timestamp('archived_at');
                $table->timestamps();

                $table->index(['user_id', 'archived_at'], 'idx_archive_user_archived');
                $table->index('original_id', 'idx_archive_original_id');
                $table->index('issue_date', 'idx_archive_issue_date');
            },
        );

        Schema::create(
            'invoice_products_archive',
            function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('original_invoice_id');
                $table->unsignedBigInteger('archive_invoice_id');
                $table->json('products_data');
                $table->timestamp('archived_at');
                $table->timestamps();

                $table->index('archive_invoice_id', 'idx_archive_products_invoice');
                $table->index('original_invoice_id', 'idx_archive_products_original');
            },
        );
    }
};
