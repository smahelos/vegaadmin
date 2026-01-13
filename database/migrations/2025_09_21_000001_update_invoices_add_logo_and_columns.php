<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bring invoices table in sync with application expectations (used by tests).
     * Adds missing columns if they don't exist to avoid SQL errors during create/update.
     */
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return; // Safety guard – base migration should have created it
        }

        Schema::table('invoices', function (Blueprint $table) {
            // Supplier relation
            if (!Schema::hasColumn('invoices', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('client_id');
            }

            // Dates and terms
            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('invoice_ss');
            }
            if (!Schema::hasColumn('invoices', 'tax_point_date')) {
                $table->date('tax_point_date')->nullable()->after('issue_date');
            }
            if (!Schema::hasColumn('invoices', 'due_in')) {
                $table->integer('due_in')->nullable()->after('tax_point_date');
            }

            // Status FK (may already be added by other migration)
            if (!Schema::hasColumn('invoices', 'payment_status_id')) {
                $table->unsignedBigInteger('payment_status_id')->nullable()->after('payment_status');
            }

            // Free-form invoice content
            if (!Schema::hasColumn('invoices', 'invoice_text')) {
                $table->text('invoice_text')->nullable()->after('payment_currency');
            }

            // Stored path to uploaded logo
            if (!Schema::hasColumn('invoices', 'invoice_logo')) {
                $table->string('invoice_logo', 512)->nullable()->after('invoice_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'invoice_logo')) {
                $table->dropColumn('invoice_logo');
            }
            if (Schema::hasColumn('invoices', 'invoice_text')) {
                $table->dropColumn('invoice_text');
            }
            if (Schema::hasColumn('invoices', 'due_in')) {
                $table->dropColumn('due_in');
            }
            if (Schema::hasColumn('invoices', 'tax_point_date')) {
                $table->dropColumn('tax_point_date');
            }
            if (Schema::hasColumn('invoices', 'issue_date')) {
                $table->dropColumn('issue_date');
            }
            if (Schema::hasColumn('invoices', 'supplier_id')) {
                $table->dropColumn('supplier_id');
            }
        });
    }
};
