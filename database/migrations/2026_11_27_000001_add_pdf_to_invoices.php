<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issuing stores the rendered PDF with its SHA-256 hash; a download returns that file (D-050, points 5 and 8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->string('pdf_path')->nullable()->after('register_entry');
                $table->char('pdf_hash', 64)->nullable()->after('pdf_path');
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->dropColumn(['pdf_path', 'pdf_hash']);
            },
        );
    }
};
