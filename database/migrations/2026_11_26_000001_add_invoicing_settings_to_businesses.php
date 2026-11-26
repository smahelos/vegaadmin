<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The VAT payer flag, the register entry and the default document language of a business (D-050, points 6 and 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'businesses',
            function (Blueprint $table): void {
                $table->boolean('is_vat_payer')->default(false)->after('vat_number');
                $table->string('register_entry')->nullable()->after('is_vat_payer');
                $table->string('document_language')->default('cs')->after('register_entry');
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'businesses',
            function (Blueprint $table): void {
                $table->dropColumn(
                    [
                        'is_vat_payer',
                        'register_entry',
                        'document_language',
                    ],
                );
            },
        );
    }
};
