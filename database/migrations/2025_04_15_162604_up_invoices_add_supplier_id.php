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
            // Přidání sloupce user_id jako cizí klíč
            $table->unsignedBigInteger('supplier_id')->nullable()->after('user_id');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Odstranění cizího klíče
            $table->dropForeign(['supplier_id']);
            
            // Odstranění sloupců
            $table->dropColumn(['supplier_id']);
        });
    }
};
