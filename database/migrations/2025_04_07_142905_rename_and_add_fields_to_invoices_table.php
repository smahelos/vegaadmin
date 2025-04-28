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
        Schema::table('invoices', function (Blueprint $table) {
            // Přejmenování sloupce payment_draft_date na issue_date
            $table->renameColumn('payment_draft_date', 'issue_date');
            
            // Přidání nového sloupce tax_point_date
            $table->date('tax_point_date')->nullable()->after('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Vrácení původního názvu sloupce
            $table->renameColumn('issue_date', 'payment_draft_date');
            
            // Odebrání přidaného sloupce
            $table->dropColumn('tax_point_date');
        });
    }
};
