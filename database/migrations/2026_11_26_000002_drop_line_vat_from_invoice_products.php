<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VAT is computed by rate from the unrounded line bases (D-050, point 7), so a line keeps no VAT or total of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'invoice_products',
            function (Blueprint $table): void {
                $table->dropColumn(['tax_amount', 'total_price']);
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'invoice_products',
            function (Blueprint $table): void {
                $table->decimal(
                    'tax_amount',
                    18,
                    2,
                )->default(0)->after('tax_rate');
                $table->decimal(
                    'total_price',
                    18,
                    2,
                )->default(0)->after('tax_amount');
            },
        );
    }
};
