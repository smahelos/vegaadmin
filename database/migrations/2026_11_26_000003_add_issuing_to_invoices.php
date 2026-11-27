<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An invoice is a draft until it is issued: issuing assigns the number and the dates, copies the supplier's
 * invoicing settings and stores the VAT lines by rate (D-050, points 1, 6, 7 and 9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->string('invoice_vs', 50)->nullable()->change();
                $table->date('issue_date')->nullable()->change();
                $table->date('tax_point_date')->nullable()->change();
                $table->string('document_language', 2)->default('cs')->after('template');
                $table->dateTime('issued_at')->nullable()->after('document_language');
                $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->restrictOnDelete();
                $table->boolean('is_vat_payer')->nullable()->after('issued_by');
                $table->string('register_entry')->nullable()->after('is_vat_payer');
            },
        );

        // No stored invoice is converted to an issued one (D-050): every existing row becomes a draft
        $invoices = DB::table('invoices');
        $invoices->update(
            [
                'invoice_vs' => null,
                'issue_date' => null,
                'tax_point_date' => null,
            ],
        );

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->dropIndex('idx_invoices_user_invoice_vs');
                $table->unique(['user_id', 'invoice_vs'], 'invoices_user_number_unique');
            },
        );

        Schema::create(
            'vat_lines',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->decimal(
                    'rate',
                    5,
                    2,
                );
                $table->decimal(
                    'base',
                    18,
                    2,
                );
                $table->decimal(
                    'vat',
                    18,
                    2,
                );
                $table->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_lines');

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->dropUnique('invoices_user_number_unique');
                $table->index(['user_id', 'invoice_vs'], 'idx_invoices_user_invoice_vs');
                $table->dropConstrainedForeignId('issued_by');
                $table->dropColumn(
                    [
                        'document_language',
                        'issued_at',
                        'is_vat_payer',
                        'register_entry',
                    ],
                );
            },
        );

        // Drafts have no number or dates; the restored columns need a value
        $invoices = DB::table('invoices');
        $invoices->whereNull('invoice_vs');
        $invoices->update(
            ['invoice_vs' => DB::raw('id')],
        );
        $invoices = DB::table('invoices');
        $invoices->whereNull('issue_date');
        $invoices->update(
            [
                'issue_date' => DB::raw('DATE(created_at)'),
                'tax_point_date' => DB::raw('DATE(created_at)'),
            ],
        );

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->string('invoice_vs', 50)->nullable(false)->change();
                $table->date('issue_date')->nullable(false)->change();
                $table->date('tax_point_date')->nullable(false)->change();
            },
        );
    }
};
