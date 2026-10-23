<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quantities were whole numbers (1.5 hours was stored as 2) and the invoice total lost its cents.
     * Line and invoice amounts get room for a large invoice: 99 999.999 units at 99 999 999.99 plus VAT.
     */
    public function up(): void
    {
        Schema::table('invoice_products', function (Blueprint $table): void {
            $table->decimal('quantity', 10, 3)->default(1)->change();
            $table->decimal('tax_amount', 18, 2)->default(0)->change();
            $table->decimal('total_price', 18, 2)->default(0)->change();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal(
                'payment_amount',
                18,
                2,
            )->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->integer('payment_amount')->change();
        });

        Schema::table('invoice_products', function (Blueprint $table): void {
            $table->integer('quantity')->default(1)->change();
            $table->decimal('tax_amount', 10, 2)->default(0)->change();
            $table->decimal('total_price', 10, 2)->default(0)->change();
        });
    }
};
