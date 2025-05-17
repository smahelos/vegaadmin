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
        Schema::table('invoice_products', function (Blueprint $table) {
            // Přidání sloupců pro rozšíření pivotní tabulky
            $table->string('name')->default(null)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropColumn([
                'name'
            ]);
        });
    }
};
