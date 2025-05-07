<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Upravit sloupce invoice_ks a invoice_ss jako nullable
            $table->string('invoice_ks')->nullable()->change();
            $table->string('invoice_ss')->nullable()->change();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Vrátit sloupce zpět jako povinné (not nullable)
            $table->string('invoice_ks')->nullable(false)->change();
            $table->string('invoice_ss')->nullable(false)->change();
        });
    }
};